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
$selectedPersonId = $selectionOverrides['selectedPersonId'] ?? null;
$selectedPersonName = $selectionOverrides['selectedPersonName'] ?? null;
$selectedTeamId = $selectionOverrides['selectedTeamId'] ?? null;
$selectedTeamSeasonId = $selectionOverrides['selectedTeamSeasonId'] ?? null;
$selectedGameId = $selectionOverrides['selectedGameId'] ?? null;
$selectedSiteId = $selectionOverrides['selectedSiteId'] ?? null;
$selectedOpponentId = $selectionOverrides['selectedOpponentId'] ?? null;
$selectedSportId = $selectionOverrides['selectedSportId'] ?? null;
$selectedRosterId = $selectionOverrides['selectedRosterId'] ?? null;

$personService = new \App\Service\PersonService();
$rosterService = new \App\Service\TeamSeasonRosterService();
foreach ($currentTags as $tag) {
    $slug = (string)($tag->slug ?? $tag['slug'] ?? '');
    if ($slug === '') {
        continue;
    }
    if (str_starts_with($slug, 'person-')) {
        $selectedPersonId = (int)substr($slug, strlen('person-'));
        $selectedPersonName = $personService->getDisplayLabel($selectedPersonId);
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
        if (!$selectedPersonId && $selectedRosterId) {
            $rosterData = $rosterService->getRosterDisplayData($selectedRosterId);
            $selectedPersonId = $rosterData['person_id'] ?? null;
            $selectedPersonName = $rosterData['person_label'] ?? $selectedPersonName;
        }
    }
}

$initialPersonId = (int)($selectedPersonId ?? 0);
$initialRosterId = (int)($selectedRosterId ?? 0);
?>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Person</label>
        <input
            type="text"
            name="person_search"
            id="person_search"
            list="personsList"
            class="form-control"
            placeholder="Search person by name"
            autocomplete="off"
            value="<?= h($selectedPersonName ?? '') ?>"
        />
        <datalist id="personsList"></datalist>
        <input type="hidden" name="person_select" id="person_select" value="<?= h($initialPersonId) ?>" />
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
        <select name="game_select" id="game_select" class="form-select">
            <option value="">-- select game --</option>
            <?php foreach ($games as $g): ?>
                <option
                    value="<?= h($g['id']) ?>"
                    data-teamseason="<?= h($g['team_season_id'] ?? '') ?>"
                    <?= $selectedGameId === (int)$g['id'] ? 'selected' : '' ?>
                >
                    <?= h($g['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">Must select a Team Season first.</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Site</label>
        <select name="site_select" id="site_select" class="form-select">
            <option value="">-- select site --</option>
            <?php foreach ($sites as $s): ?>
                <option value="<?= h($s['id']) ?>" <?= $selectedSiteId === (int)$s['id'] ? 'selected' : '' ?>><?= h($s['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Opponent</label>
        <select name="opponent_select" id="opponent_select" class="form-select">
            <option value="">-- select opponent --</option>
            <?php foreach ($opponents as $o): ?>
                <option value="<?= h($o['id']) ?>" <?= $selectedOpponentId === (int)$o['id'] ? 'selected' : '' ?>><?= h($o['label']) ?></option>
            <?php endforeach; ?>
        </select>
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
        <select name="roster_select" id="roster_select" class="form-select" <?= $initialPersonId ? '' : 'disabled' ?> >
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
$selectedPersonIdJs = (int)$initialPersonId;
$script = <<<JS
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const list = document.getElementById('personsList');
        const search = document.getElementById('person_search');
        const hidden = document.getElementById('person_select');
        const rosterSelect = document.getElementById('roster_select');
        const teamSeasonSelect = document.getElementById('teamseason_select');
        const gameSelect = document.getElementById('game_select');
        const selectedPersonId = {$selectedPersonIdJs};
        const selectedRosterId = {$selectedRosterIdJs};
        const gameOptions = gameSelect ? Array.from(gameSelect.options).slice(1) : [];

            function filterGamesByTeamSeason() {
                if (!gameSelect || !teamSeasonSelect) return;
                const selectedTeamSeason = (teamSeasonSelect.value || '').toString().trim();
                let matchCount = 0;
                const enableSelect = !!selectedTeamSeason;

                gameOptions.forEach(opt => {
                    const teamSeason = (opt.dataset.teamseason ?? opt.getAttribute('data-teamseason') ?? '').toString().trim();
                    const matchesSeason = enableSelect && teamSeason && teamSeason === selectedTeamSeason;
                    opt.hidden = !matchesSeason;
                    if (matchesSeason) {
                        matchCount++;
                    }
                });

                const placeholder = gameSelect.options[0];
                if (!enableSelect) {
                    placeholder.textContent = '-- select game --';
                } else if (matchCount === 0) {
                    placeholder.textContent = 'No games for selected team season';
                } else {
                    placeholder.textContent = '-- select game --';
                }

                if (!enableSelect) {
                    gameSelect.disabled = true;
                } else {
                    gameSelect.disabled = matchCount === 0;
                }
            }

        function renderDatalistFromPersons(persons) {
            if (!list) return;
            list.innerHTML = '';
            persons.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.label;
                list.appendChild(opt);
            });
        }

        function setHiddenFromName(name) {
            if (!hidden) return;
            if (!name) {
                hidden.value = '';
                return;
            }
            const found = lastPersons.find(p => p.label === name);
            hidden.value = found ? found.id : '';
        }

        function fetchPersons(query) {
            if (!query || query.trim() === '') {
                lastPersons = [];
                renderDatalistFromPersons([]);
                return Promise.resolve();
            }
            return fetch('/admin/images/persons?q=' + encodeURIComponent(query), {credentials: 'same-origin'})
                .then(r => r.json())
                .then(data => {
                    if (data && Array.isArray(data.persons)) {
                        lastPersons = data.persons;
                        renderDatalistFromPersons(lastPersons);
                    } else {
                        lastPersons = [];
                        renderDatalistFromPersons([]);
                    }
                })
                .catch(() => {
                    lastPersons = [];
                    renderDatalistFromPersons([]);
                });
        }

        function debounce(fn, ms) {
            let t = null;
            return function (...args) {
                if (t) clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), ms);
            };
        }

        function populateRostersForPerson(personId, preselectId) {
            if (!rosterSelect) return;
            if (!personId) {
                rosterSelect.innerHTML = '<option value="">-- select roster entry --</option>';
                rosterSelect.disabled = true;
                return;
            }
            fetch('/admin/images/rosters?person_id=' + encodeURIComponent(personId), {credentials: 'same-origin'})
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

        let lastPersons = [];
        if (search) {
            const debouncedFetch = debounce((val) => {
                fetchPersons(val || '').then(() => {
                    setHiddenFromName(val || '');
                    const pid = hidden ? (hidden.value || '') : '';
                    populateRostersForPerson(pid, selectedRosterId);
                });
            }, 250);

            search.addEventListener('input', (e) => {
                const val = e.target.value.trim();
                debouncedFetch(val);
            });
            const form = search.closest('form');
            if (form) {
                form.addEventListener('submit', () => {
                    if (hidden && hidden.value) {
                        return;
                    }
                    const val = (search.value || '').trim();
                    if (!val) {
                        return;
                    }
                    let found = lastPersons.find(p => p.label.toLowerCase() === val.toLowerCase());
                    if (!found) {
                        found = lastPersons.find(p => p.label.toLowerCase().startsWith(val.toLowerCase()));
                    }
                    if (hidden) {
                        hidden.value = found ? found.id : '';
                    }
                });
            }
        }

        if (teamSeasonSelect) {
            teamSeasonSelect.addEventListener('change', filterGamesByTeamSeason);
        }

        function init() {
            if (selectedPersonId) {
                populateRostersForPerson(selectedPersonId, selectedRosterId);
            } else if (rosterSelect) {
                rosterSelect.disabled = true;
            }
            filterGamesByTeamSeason();
        }

        init();
    });
})();
JS;

echo $this->Html->scriptBlock($script, ['block' => true]);
