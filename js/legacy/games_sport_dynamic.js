/*
 * Dynamic sport-aware game form enhancer
 */
function getSportFormElements() {
    const select = document.getElementById("team-season-select");
    const section = document.getElementById("sport-specific-section");
    const indicator = document.getElementById("sport-indicator");
    const sportNameSpan = document.getElementById("current-sport");
    const loading = document.getElementById("sport-loading");
    const url = select?.getAttribute("data-sport-url") || "";
    const gameIdEl = document.getElementById("game-id-hidden");
    const existingGameId = gameIdEl ? gameIdEl.value : null;

    return {
        select,
        section,
        indicator,
        sportNameSpan,
        loading,
        url,
        existingGameId,
    };
}

function buildFieldControl(field, existingValues) {
    const wrapper = document.createElement("div");
    wrapper.className = "col-md-3 mb-2";
    const name = field.field_name;
    const label = field.display_label || name;
    const value = existingValues[name] || field.default_value || "";
    const input = document.createElement("input");
    input.name = name;
    input.id = "field-" + name;
    input.className = "form-control";
    input.value = value;
    if (field.field_type === "number") {
        input.type = "number";
        if (field.min !== undefined) {
            input.min = field.min;
        }
        if (field.max !== undefined) {
            input.max = field.max;
        }
    } else {
        input.type = "text";
    }
    const labelEl = document.createElement("label");
    labelEl.className = "form-label";
    labelEl.setAttribute("for", input.id);
    labelEl.textContent = label;
    wrapper.appendChild(labelEl);
    wrapper.appendChild(input);

    return wrapper;
}

function groupFields(fields) {
    const groups = {};
    fields.forEach((f) => {
        const g = f.field_group || "general";
        if (!groups[g]) {
            groups[g] = [];
        }
        groups[g].push(f);
    });

    return groups;
}

function renderEav(template, existingValues) {
    const { section } = getSportFormElements();
    if (!section) {
        return;
    }

    section.innerHTML = "";
    const heading = document.createElement("h5");
    heading.textContent = "Sport-Specific Details";
    section.appendChild(heading);

    const groups = groupFields(template);
    Object.keys(groups).forEach((groupName) => {
        const card = document.createElement("div");
        card.className = "card mt-2";
        const header = document.createElement("div");
        header.className = "card-header";
        const h6 = document.createElement("h6");
        h6.className = "mb-0";
        h6.textContent =
            groupName.charAt(0).toUpperCase() + groupName.slice(1);
        header.appendChild(h6);
        const body = document.createElement("div");
        body.className = "card-body";

        const fields = groups[groupName];
        const chunkSize = 4;
        for (let i = 0; i < fields.length; i += chunkSize) {
            const row = document.createElement("div");
            row.className = "row";
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

async function fetchMeta(teamSeasonId) {
    const { section, indicator, loading, sportNameSpan, url, existingGameId } =
        getSportFormElements();
    if (!url) {
        return;
    }

    if (indicator) {
        indicator.style.display = "block";
    }
    if (loading) {
        loading.style.display = "inline-block";
    }

    const params = new URLSearchParams();
    if (teamSeasonId) {
        params.append("team_season_id", teamSeasonId);
    }
    if (existingGameId) {
        params.append("game_id", existingGameId);
    }

    try {
        const htmlResp = await fetch(
            url + "?" + params.toString() + "&format=html",
            {
                headers: { Accept: "text/html" },
            },
        );
        if (htmlResp.ok) {
            const text = await htmlResp.text();
            if (section) {
                section.innerHTML = text;
            }

            if (sportNameSpan) {
                const tmp = document.createElement("div");
                tmp.innerHTML = text;
                const sn = tmp.querySelector("[data-sport-name]");
                if (sn) {
                    sportNameSpan.textContent = sn.getAttribute("data-sport-name");
                }
            }

            return;
        }
    } catch {
        // Fall back to JSON path below.
    }

    try {
        const resp = await fetch(url + "?" + params.toString(), {
            headers: { Accept: "application/json" },
        });
        const data = await resp.json();
        if (!data.success) {
            return;
        }

        if (sportNameSpan) {
            sportNameSpan.textContent = data.sportName || "Unknown";
        }
        renderEav(data.eavTemplate || [], data.values || {});
    } catch {
        console.warn("Failed to load sport meta");
    } finally {
        if (loading) {
            loading.style.display = "none";
        }
    }
}

function initGamesSportDynamic() {
    const { select, section, existingGameId } = getSportFormElements();
    if (!select) {
        return;
    }

    if (select.dataset.gamesSportDynamicBound !== "true") {
        select.addEventListener("change", function () {
            const tsid = this.value;
            if (tsid) {
                void fetchMeta(tsid);
            }
        });
        select.dataset.gamesSportDynamicBound = "true";
    }

    if (
        (select.value || existingGameId) &&
        section &&
        !section.querySelector(".card")
    ) {
        void fetchMeta(select.value || "");
    }
}

if (typeof document !== "undefined") {
    initGamesSportDynamic();
}

if (typeof module !== "undefined" && module && module.exports) {
    module.exports = {
        buildFieldControl,
        groupFields,
        fetchMeta,
        renderEav,
        initGamesSportDynamic,
    };
}
