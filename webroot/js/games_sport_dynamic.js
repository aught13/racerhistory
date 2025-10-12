/*
 * Dynamic sport-aware game form enhancer
 */
/* global URLSearchParams, module */
(function () {
    const select = document.getElementById('team-season-select');
    if (!select) return;
    const section = document.getElementById('sport-specific-section');
    const indicator = document.getElementById('sport-indicator');
    const sportNameSpan = document.getElementById('current-sport');
    const loading = document.getElementById('sport-loading');
    const url = select.getAttribute('data-sport-url');
    const gameIdEl = document.getElementById('game-id-hidden');
    const existingGameId = gameIdEl ? gameIdEl.value : null;

    function buildFieldControl(field, existingValues) {
        const wrapper = document.createElement('div');
        wrapper.className = 'col-md-3 mb-2';
        const name = field.field_name;
        const label = field.display_label || name;
        const value = existingValues[name] || field.default_value || '';
        const input = document.createElement('input');
        input.name = name;
        input.id = 'field-' + name;
        input.className = 'form-control';
        input.value = value;
        if (field.field_type === 'number') {
            input.type = 'number';
            if (field.min !== undefined) input.min = field.min;
            if (field.max !== undefined) input.max = field.max;
        } else {
            input.type = 'text';
        }
        const labelEl = document.createElement('label');
        labelEl.className = 'form-label';
        labelEl.setAttribute('for', input.id);
        labelEl.textContent = label;
        wrapper.appendChild(labelEl);
        wrapper.appendChild(input);
        return wrapper;
    }

    function groupFields(fields) {
        const groups = {};
        fields.forEach((f) => {
            const g = f.field_group || 'general';
            if (!groups[g]) groups[g] = [];
            groups[g].push(f);
        });
        return groups;
    }

    async function fetchMeta(teamSeasonId) {
        if (!url) return;
        indicator && (indicator.style.display = 'block');
        loading && (loading.style.display = 'inline-block');
        const params = new URLSearchParams();
        if (teamSeasonId) params.append('team_season_id', teamSeasonId);
        if (existingGameId) params.append('game_id', existingGameId);
        // Try to fetch server-rendered HTML fragment first
        try {
            const htmlResp = await fetch(url + '?' + params.toString() + '&format=html', {
                headers: { Accept: 'text/html' },
            });
            if (htmlResp.ok) {
                const text = await htmlResp.text();
                if (section) {
                    section.innerHTML = text;
                }
                // Try to update sport name from injected fragment if possible
                if (sportNameSpan) {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = text;
                    const sn = tmp.querySelector('[data-sport-name]');
                    if (sn) sportNameSpan.textContent = sn.getAttribute('data-sport-name');
                }
                return;
            }
        } catch {
            // Fall back to JSON path below
            // console.warn('HTML fragment fetch failed, falling back to JSON');
        }

        // Fallback: fetch JSON and render client-side
        try {
            const resp = await fetch(url + '?' + params.toString(), {
                headers: { Accept: 'application/json' },
            });
            const data = await resp.json();
            if (!data.success) return;
            if (sportNameSpan) sportNameSpan.textContent = data.sportName || 'Unknown';
            renderEav(data.eavTemplate || [], data.values || {});
        } catch {
            console.warn('Failed to load sport meta');
        } finally {
            loading && (loading.style.display = 'none');
        }
    }

    function renderEav(template, existingValues) {
        if (!section) return;
        section.innerHTML = '';
        const heading = document.createElement('h5');
        heading.textContent = 'Sport-Specific Details';
        section.appendChild(heading);

        const groups = groupFields(template);
        Object.keys(groups).forEach((groupName) => {
            const card = document.createElement('div');
            card.className = 'card mt-2';
            const header = document.createElement('div');
            header.className = 'card-header';
            const h6 = document.createElement('h6');
            h6.className = 'mb-0';
            h6.textContent = groupName.charAt(0).toUpperCase() + groupName.slice(1);
            header.appendChild(h6);
            const body = document.createElement('div');
            body.className = 'card-body';

            // Create rows and chunk fields into up to 4 columns per row for responsive layout
            const fields = groups[groupName];
            const chunkSize = 4;
            for (let i = 0; i < fields.length; i += chunkSize) {
                const row = document.createElement('div');
                row.className = 'row';
                const slice = fields.slice(i, i + chunkSize);
                slice.forEach((field) => {
                    row.appendChild(buildFieldControl(field, existingValues));
                });
                body.appendChild(row);
            }
            card.appendChild(header);
            card.appendChild(body);
            section.appendChild(card);
        });
    }

    select.addEventListener('change', function () {
        const tsid = this.value;
        if (tsid) fetchMeta(tsid);
    });

    // Auto-load on first render if selection preset and no template present
    if ((select.value || existingGameId) && section && !section.querySelector('.card')) {
        fetchMeta(select.value || '');
    }
    // Expose helpers for unit tests when running in Node/CommonJS
    if (typeof module !== 'undefined' && module && module.exports) {
        module.exports = {
            buildFieldControl,
            groupFields,
            fetchMeta,
            renderEav,
        };
    }
})();
