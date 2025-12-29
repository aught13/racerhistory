<?php
declare(strict_types=1);

$teams = is_iterable($teams ?? []) ? $teams : [];
$teamSeasons = is_iterable($teamSeasons ?? []) ? $teamSeasons : [];
$games = is_iterable($games ?? []) ? $games : [];
$sites = is_iterable($sites ?? []) ? $sites : [];
$opponents = is_iterable($opponents ?? []) ? $opponents : [];
$sports = is_iterable($sports ?? []) ? $sports : [];
$currentTags = is_iterable($currentTags ?? []) ? $currentTags : [];
$tagStringValue = (string)($tagString ?? '');
$showTeamId = !empty($showTeamId);

$freeform = array_merge([
    'type' => 'textarea',
    'name' => 'tags',
    'value' => $tagStringValue,
    'label' => 'Additional Tags (comma-separated)',
    'help' => 'Freeform tags will be included along with entity tags.',
    'attributes' => [
        'rows' => 3,
        'id' => 'tagsInput',
    ],
], (array)($freeform ?? []));
if (empty($freeform['attributes']['class'])) {
    $freeform['attributes']['class'] = 'form-control';
}
$freeform['value'] = (string)($freeform['value'] ?? '');

$selectionOverrides = (array)($tagSelection ?? []);
$selectedPersonIds = $selectionOverrides['selectedPersonIds'] ?? null;
$selectedPersonNames = $selectionOverrides['selectedPersonNames'] ?? null;
$selectedTeamId = $selectionOverrides['selectedTeamId'] ?? null;
$selectedTeamSeasonId = $selectionOverrides['selectedTeamSeasonId'] ?? null;
$selectedGameId = $selectionOverrides['selectedGameId'] ?? null;
$selectedSiteId = $selectionOverrides['selectedSiteId'] ?? null;
$selectedOpponentId = $selectionOverrides['selectedOpponentId'] ?? null;
$selectedSportId = $selectionOverrides['selectedSportId'] ?? null;
$selectedRosterId = $selectionOverrides['selectedRosterId'] ?? null;

if ($selectedPersonIds !== null && !is_array($selectedPersonIds)) {
    $selectedPersonIds = [(int)$selectedPersonIds];
}
if ($selectedPersonIds === null) {
    $selectedPersonIds = [];
}

$personService = new \App\Service\PersonService();
$rosterService = new \App\Service\TeamSeasonRosterService();
foreach ($currentTags as $tag) {
    $slug = (string)($tag->slug ?? $tag['slug'] ?? '');
    if ($slug === '') {
        continue;
    }
    if (str_starts_with($slug, 'person-')) {
        $selectedPersonIds[] = (int)substr($slug, strlen('person-'));
    } elseif (str_starts_with($slug, 'teamseason-')) {
        $selectedTeamSeasonId = (int)substr($slug, strlen('teamseason-'));
    } elseif (str_starts_with($slug, 'game-')) {
        $selectedGameId = (int)substr($slug, strlen('game-'));
    } elseif (str_starts_with($slug, 'site-')) {
        $selectedSiteId = (int)substr($slug, strlen('site-'));
    } elseif (str_starts_with($slug, 'opponent-')) {
        $selectedOpponentId = (int)substr($slug, strlen('opponent-'));
    } elseif (str_starts_with($slug, 'team-')) {
        $selectedTeamId = (int)substr($slug, strlen('team-'));
    } elseif (str_starts_with($slug, 'sport-')) {
        $selectedSportId = (int)substr($slug, strlen('sport-'));
    } elseif (str_starts_with($slug, 'team_season_roster-')) {
        $selectedRosterId = (int)substr($slug, strlen('team_season_roster-'));
        if (!$selectedPersonIds && $selectedRosterId) {
            $rosterData = $rosterService->getRosterDisplayData($selectedRosterId);
            $selectedPersonIds = [(int)($rosterData['person_id'] ?? 0)];
            $selectedPersonNames = [(string)($rosterData['person_label'] ?? '')];
        }
    }
}

$selectedPersonIds = array_values(array_unique(array_map('intval', $selectedPersonIds)));
$selectedPersons = [];
foreach ($selectedPersonIds as $pid) {
    if ($pid <= 0) {
        continue;
    }
    $selectedPersons[] = ['id' => $pid, 'label' => $personService->getDisplayLabel($pid)];
}

$selectedGameLabel = '';
if ($selectedGameId) {
    foreach ($games as $g) {
        if ((int)($g['id'] ?? 0) === (int)$selectedGameId) {
            $selectedGameLabel = (string)($g['label'] ?? '');
            break;
        }
    }
}
$selectedSiteLabel = '';
if ($selectedSiteId) {
    foreach ($sites as $s) {
        if ((int)($s['id'] ?? 0) === (int)$selectedSiteId) {
            $selectedSiteLabel = (string)($s['label'] ?? '');
            break;
        }
    }
}
$selectedOpponentLabel = '';
if ($selectedOpponentId) {
    foreach ($opponents as $o) {
        if ((int)($o['id'] ?? 0) === (int)$selectedOpponentId) {
            $selectedOpponentLabel = (string)($o['label'] ?? '');
            break;
        }
    }
}

$initialPersonCount = count($selectedPersons);
$initialPersonIds = array_map(fn(array $p) => (int)$p['id'], $selectedPersons);
$initialRosterId = (int)($selectedRosterId ?? 0);
?>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Person</label>
        <div id="selectedPersons" class="d-flex flex-wrap gap-2 mb-2"></div>
        <input
            type="text"
            name="person_search"
            id="person_search"
            list="personsList"
            class="form-control"
            placeholder="Search person by name"
            autocomplete="off"
        />
        <div class="d-flex gap-2 mt-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="add_person_btn">Add Person</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear_persons_btn">Clear</button>
        </div>
        <datalist id="personsList"></datalist>
        <div id="person_hidden_inputs">
            <?php foreach ($selectedPersons as $p): ?>
                <input type="hidden" name="person_select[]" value="<?= h((int)$p['id']) ?>" />
            <?php endforeach; ?>
        </div>
        <div class="form-text">You can tag multiple people. If a roster entry is selected, only one person may be tagged.</div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Team</label>
        <select name="team_select" id="team_select" class="form-select">
            <option value="">-- select team --</option>
            <?= $this->element('Admin/team_select_options', [
                'teams' => $teams,
                'selectedValue' => $selectedTeamId,
                'showId' => $showTeamId,
            ]) ?>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Team Season</label>
        <select name="teamseason_select" id="teamseason_select" class="form-select">
            <option value="">-- select team season --</option>
            <?php foreach ($teamSeasons as $ts): ?>
                <option value="<?= h($ts['id']) ?>" <?= $selectedTeamSeasonId === (int)$ts['id'] ? 'selected' : '' ?>><?= h($ts['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Game</label>
        <input
            type="text"
            name="game_search"
            id="game_search"
            list="gamesList"
            class="form-control"
            placeholder="Search game"
            autocomplete="off"
            value="<?= h($selectedGameLabel) ?>"
            <?= $selectedTeamSeasonId ? '' : 'disabled' ?>
        />
        <datalist id="gamesList"></datalist>
        <input type="hidden" name="game_select" id="game_select" value="<?= h((int)$selectedGameId) ?>" />
        <div class="form-text">Must select a Team Season first.</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Site</label>
        <input
            type="text"
            name="site_search"
            id="site_search"
            list="sitesList"
            class="form-control"
            placeholder="Search site"
            autocomplete="off"
            value="<?= h($selectedSiteLabel) ?>"
        />
        <datalist id="sitesList"></datalist>
        <input type="hidden" name="site_select" id="site_select" value="<?= h((int)$selectedSiteId) ?>" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Opponent</label>
        <input
            type="text"
            name="opponent_search"
            id="opponent_search"
            list="opponentsList"
            class="form-control"
            placeholder="Search opponent"
            autocomplete="off"
            value="<?= h($selectedOpponentLabel) ?>"
        />
        <datalist id="opponentsList"></datalist>
        <input type="hidden" name="opponent_select" id="opponent_select" value="<?= h((int)$selectedOpponentId) ?>" />
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Sport</label>
        <select name="sport_select" id="sport_select" class="form-select">
            <option value="">-- select sport --</option>
            <?php foreach ($sports as $sp): ?>
                <option value="<?= h($sp['id']) ?>" <?= $selectedSportId === (int)$sp['id'] ? 'selected' : '' ?>><?= h($sp['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Team Season Roster Entry</label>
        <select name="roster_select" id="roster_select" class="form-select" <?= $initialPersonCount === 1 ? '' : 'disabled' ?> >
            <option value="">-- select roster entry --</option>
        </select>
        <div class="form-text">Must select a Person first. Other tags cannot be set when a Roster entry is selected.</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="<?= h($freeform['attributes']['id']) ?>"><?= h($freeform['label']) ?></label>
    <?php if ($freeform['type'] === 'textarea'): ?>
        <textarea name="<?= h($freeform['name']) ?>" <?php foreach ($freeform['attributes'] as $attr => $value): ?> <?= h($attr) ?>="<?= h($value) ?>"<?php endforeach; ?>><?= h($freeform['value']) ?></textarea>
    <?php else: ?>
        <input type="<?= h($freeform['type']) ?>" name="<?= h($freeform['name']) ?>" value="<?= h($freeform['value']) ?>" <?php foreach ($freeform['attributes'] as $attr => $value): ?> <?= h($attr) ?>="<?= h($value) ?>"<?php endforeach; ?> />
    <?php endif; ?>
    <?php if (!empty($freeform['help'])): ?>
        <div class="form-text"><?= h($freeform['help']) ?></div>
    <?php endif; ?>
</div>

<?php
$selectedRosterIdJs = (int)$initialRosterId;
$initialSelectedPersonsJson = json_encode($selectedPersons, JSON_THROW_ON_ERROR);
$script = <<<JS
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const list = document.getElementById('personsList');
        const selectedPersonsEl = document.getElementById('selectedPersons');
        const hiddenWrap = document.getElementById('person_hidden_inputs');
        const search = document.getElementById('person_search');
        const addBtn = document.getElementById('add_person_btn');
        const clearBtn = document.getElementById('clear_persons_btn');
        const rosterSelect = document.getElementById('roster_select');
        const teamSeasonSelect = document.getElementById('teamseason_select');
        const teamSelect = document.getElementById('team_select');
        const sportSelect = document.getElementById('sport_select');
        const gameSearch = document.getElementById('game_search');
        const gameHidden = document.getElementById('game_select');
        const gamesList = document.getElementById('gamesList');
        const siteSearch = document.getElementById('site_search');
        const siteHidden = document.getElementById('site_select');
        const sitesList = document.getElementById('sitesList');
        const opponentSearch = document.getElementById('opponent_search');
        const opponentHidden = document.getElementById('opponent_select');
        const opponentsList = document.getElementById('opponentsList');
        const initialSelectedPersons = {$initialSelectedPersonsJson};
        const selectedRosterId = {$selectedRosterIdJs};
        const endpoints = {
            persons: '/admin/tag-lookups/persons',
            rosters: '/admin/tag-lookups/rosters',
            games: '/admin/tag-lookups/games',
            sites: '/admin/tag-lookups/sites',
            opponents: '/admin/tag-lookups/opponents'
        };

        let selectedPersons = Array.isArray(initialSelectedPersons)
            ? initialSelectedPersons.map(p => ({id: parseInt(p.id, 10), label: String(p.label || '')})).filter(p => !!p.id)
            : [];
        let lastPersons = [];
        let lastGames = [];
        let lastSites = [];
        let lastOpponents = [];

        function debounce(fn, ms) {
            let t = null;
            return function (...args) {
                if (t) clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), ms);
            };
        }

        function renderDatalist(listEl, rows) {
            if (!listEl) return;
            listEl.innerHTML = '';
            rows.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.label;
                listEl.appendChild(opt);
            });
        }

        function setHiddenFromLabel(hiddenEl, label, rows) {
            if (!hiddenEl) return;
            const val = (label || '').trim();
            if (!val) {
                hiddenEl.value = '';
                return;
            }
            const found = rows.find(r => (r.label || '') === val);
            hiddenEl.value = found ? String(found.id) : '';
        }

        function setRosterEnabled(enabled) {
            if (!rosterSelect) return;
            rosterSelect.disabled = !enabled;
            if (!enabled) {
                rosterSelect.value = '';
                rosterSelect.innerHTML = '<option value="">-- select roster entry --</option>';
            }
        }

        function setOtherTagInputsEnabled(enabled) {
            const els = [teamSelect, teamSeasonSelect, gameSearch, siteSearch, opponentSearch, sportSelect];
            els.forEach(el => {
                if (!el) return;
                el.disabled = !enabled;
            });
        }

        function setPersonUiLocked(locked) {
            if (search) search.disabled = locked;
            if (addBtn) addBtn.disabled = locked;
            if (clearBtn) clearBtn.disabled = locked;
            if (!selectedPersonsEl) return;
            selectedPersonsEl.querySelectorAll('button[data-remove-person]').forEach(btn => {
                btn.disabled = locked;
            });
        }

        function renderSelectedPersons() {
            if (!selectedPersonsEl || !hiddenWrap) return;
            selectedPersonsEl.innerHTML = '';
            hiddenWrap.innerHTML = '';

            selectedPersons.forEach(p => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-secondary d-inline-flex align-items-center gap-2';
                badge.textContent = p.label;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-light py-0 px-1';
                btn.setAttribute('aria-label', 'Remove ' + p.label);
                btn.dataset.removePerson = String(p.id);
                btn.textContent = '×';
                btn.addEventListener('click', () => {
                    removePerson(p.id);
                });

                badge.appendChild(btn);
                selectedPersonsEl.appendChild(badge);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'person_select[]';
                hidden.value = String(p.id);
                hiddenWrap.appendChild(hidden);
            });

            const hasRoster = rosterSelect && rosterSelect.value;
            if (hasRoster) {
                setRosterEnabled(true);
                if (selectedPersons.length === 1) {
                    populateRostersForPerson(selectedPersons[0].id, selectedRosterId);
                }
                setPersonUiLocked(true);
                setOtherTagInputsEnabled(false);
                return;
            }

            setPersonUiLocked(false);
            setOtherTagInputsEnabled(true);

            if (selectedPersons.length === 1) {
                setRosterEnabled(true);
                populateRostersForPerson(selectedPersons[0].id, selectedRosterId);
            } else {
                setRosterEnabled(false);
            }
        }

        function addPersonFromLabel(label) {
            const val = (label || '').trim();
            if (!val) return;
            if (rosterSelect && rosterSelect.value) return;
            const found = lastPersons.find(p => (p.label || '').toLowerCase() === val.toLowerCase())
                || lastPersons.find(p => (p.label || '').toLowerCase().startsWith(val.toLowerCase()));
            if (!found) return;
            if (selectedPersons.some(p => p.id === found.id)) return;
            selectedPersons.push({id: found.id, label: found.label});
            search.value = '';
            renderSelectedPersons();
        }

        function removePerson(id) {
            if (rosterSelect && rosterSelect.value) return;
            selectedPersons = selectedPersons.filter(p => p.id !== id);
            renderSelectedPersons();
        }

        function fetchPersons(query) {
            if (!query || query.trim() === '') {
                lastPersons = [];
                renderDatalist(list, []);
                return Promise.resolve();
            }
            return fetch(endpoints.persons + '?q=' + encodeURIComponent(query), {credentials: 'same-origin'})
                .then(r => r.json())
                .then(data => {
                    if (data && Array.isArray(data.persons)) {
                        lastPersons = data.persons;
                        renderDatalist(list, lastPersons);
                    } else {
                        lastPersons = [];
                        renderDatalist(list, []);
                    }
                })
                .catch(() => {
                    lastPersons = [];
                    renderDatalist(list, []);
                });
        }

        function populateRostersForPerson(personId, preselectId) {
            if (!rosterSelect) return;
            if (!personId) {
                rosterSelect.innerHTML = '<option value="">-- select roster entry --</option>';
                rosterSelect.disabled = true;
                return;
            }
            fetch(endpoints.rosters + '?person_id=' + encodeURIComponent(personId), {credentials: 'same-origin'})
                .then(r => r.json())
                .then(data => {
                    rosterSelect.innerHTML = '<option value="">-- select roster entry --</option>';
                    if (data && Array.isArray(data.rosters)) {
                        data.rosters.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item.id;
                            opt.textContent = item.label;
                            if (preselectId && parseInt(preselectId, 10) === parseInt(item.id, 10)) {
                                opt.selected = true;
                            }
                            rosterSelect.appendChild(opt);
                        });
                    }
                    rosterSelect.disabled = false;
                })
                .catch(() => {
                    rosterSelect.innerHTML = '<option value="">-- select roster entry --</option>';
                    rosterSelect.disabled = true;
                });
        }

        function fetchGames(query) {
            if (!gamesList || !teamSeasonSelect || !gameSearch) return Promise.resolve();
            const teamSeasonId = (teamSeasonSelect.value || '').trim();
            if (!teamSeasonId) {
                lastGames = [];
                renderDatalist(gamesList, []);
                return Promise.resolve();
            }
            const q = (query || '').trim();
            if (q === '') {
                lastGames = [];
                renderDatalist(gamesList, []);
                return Promise.resolve();
            }
            const url = endpoints.games + '?teamseason_id=' + encodeURIComponent(teamSeasonId) + '&q=' + encodeURIComponent(q);
            return fetch(url, {credentials: 'same-origin'})
                .then(r => r.json())
                .then(data => {
                    if (data && Array.isArray(data.games)) {
                        lastGames = data.games;
                        renderDatalist(gamesList, lastGames);
                    } else {
                        lastGames = [];
                        renderDatalist(gamesList, []);
                    }
                })
                .catch(() => {
                    lastGames = [];
                    renderDatalist(gamesList, []);
                });
        }

        function fetchSites(query) {
            if (!sitesList) return Promise.resolve();
            const q = (query || '').trim();
            if (q === '') {
                lastSites = [];
                renderDatalist(sitesList, []);
                return Promise.resolve();
            }
            return fetch(endpoints.sites + '?q=' + encodeURIComponent(q), {credentials: 'same-origin'})
                .then(r => r.json())
                .then(data => {
                    if (data && Array.isArray(data.sites)) {
                        lastSites = data.sites;
                        renderDatalist(sitesList, lastSites);
                    } else {
                        lastSites = [];
                        renderDatalist(sitesList, []);
                    }
                })
                .catch(() => {
                    lastSites = [];
                    renderDatalist(sitesList, []);
                });
        }

        function fetchOpponents(query) {
            if (!opponentsList) return Promise.resolve();
            const q = (query || '').trim();
            if (q === '') {
                lastOpponents = [];
                renderDatalist(opponentsList, []);
                return Promise.resolve();
            }
            return fetch(endpoints.opponents + '?q=' + encodeURIComponent(q), {credentials: 'same-origin'})
                .then(r => r.json())
                .then(data => {
                    if (data && Array.isArray(data.opponents)) {
                        lastOpponents = data.opponents;
                        renderDatalist(opponentsList, lastOpponents);
                    } else {
                        lastOpponents = [];
                        renderDatalist(opponentsList, []);
                    }
                })
                .catch(() => {
                    lastOpponents = [];
                    renderDatalist(opponentsList, []);
                });
        }

        if (search) {
            const debouncedFetch = debounce((val) => {
                fetchPersons(val || '');
            }, 250);

            search.addEventListener('input', (e) => {
                const val = e.target.value.trim();
                const exact = lastPersons.find(p => (p.label || '').toLowerCase() === val.toLowerCase());
                if (exact) {
                    addPersonFromLabel(exact.label);
                    return;
                }
                debouncedFetch(val);
            });

            // Picking an item from a datalist often triggers change; auto-add on change as well.
            search.addEventListener('change', () => {
                addPersonFromLabel(search.value);
            });
        }

        if (addBtn && search) {
            addBtn.addEventListener('click', () => addPersonFromLabel(search.value));
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (rosterSelect && rosterSelect.value) return;
                selectedPersons = [];
                renderSelectedPersons();
            });
        }

        if (teamSeasonSelect && gameSearch) {
            teamSeasonSelect.addEventListener('change', () => {
                const hasTs = !!(teamSeasonSelect.value || '').trim();
                gameSearch.disabled = !hasTs;
                gameSearch.value = '';
                if (gameHidden) gameHidden.value = '';
                lastGames = [];
                renderDatalist(gamesList, []);
            });
        }

        if (gameSearch) {
            const debouncedGames = debounce((val) => fetchGames(val || ''), 250);
            gameSearch.addEventListener('input', (e) => {
                const val = e.target.value || '';
                debouncedGames(val);
            });
            gameSearch.addEventListener('change', () => {
                setHiddenFromLabel(gameHidden, gameSearch.value, lastGames);
            });
        }

        if (siteSearch) {
            const debouncedSites = debounce((val) => fetchSites(val || ''), 250);
            siteSearch.addEventListener('input', (e) => {
                const val = e.target.value || '';
                debouncedSites(val);
            });
            siteSearch.addEventListener('change', () => {
                setHiddenFromLabel(siteHidden, siteSearch.value, lastSites);
            });
        }

        if (opponentSearch) {
            const debouncedOpps = debounce((val) => fetchOpponents(val || ''), 250);
            opponentSearch.addEventListener('input', (e) => {
                const val = e.target.value || '';
                debouncedOpps(val);
            });
            opponentSearch.addEventListener('change', () => {
                setHiddenFromLabel(opponentHidden, opponentSearch.value, lastOpponents);
            });
        }

        if (rosterSelect) {
            rosterSelect.addEventListener('change', () => {
                const hasRoster = !!(rosterSelect.value || '').trim();
                if (!hasRoster) {
                    setPersonUiLocked(false);
                    setOtherTagInputsEnabled(true);
                    renderSelectedPersons();
                } else {
                    if (selectedPersons.length !== 1) {
                        rosterSelect.value = '';
                        return;
                    }
                    setPersonUiLocked(true);
                    setOtherTagInputsEnabled(false);
                }
            });
        }

        function init() {
            if (rosterSelect && selectedRosterId) {
                rosterSelect.value = String(selectedRosterId);
            }
            renderSelectedPersons();
        }

        init();
    });
})();
JS;

echo $this->Html->scriptBlock($script, ['block' => true]);
