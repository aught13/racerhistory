<?php
declare(strict_types=1);

use App\Service\PersonService;
use App\Service\TeamSeasonRosterService;

/**
 * @var \App\View\AppView $this
 * @var mixed $idPrefix
 * @var mixed $tagSelection
 * @var mixed $tagString
 * @var array<\App\Model\Entity\Team> $teams <-- Add these
 * @var array<\App\Model\Entity\TeamSeason> $teamSeasons
 * @var array<\App\Model\Entity\Game> $games
 * @var array<\App\Model\Entity\Site> $sites
 * @var array<\App\Model\Entity\Opponent> $opponents
 * @var array<\App\Model\Entity\Sport> $sports
 * @var array<\App\Model\Entity\Tag> $currentTags
 */

$idPrefixBase = (string)($idPrefix ?? '');
$idPrefixNormalized = $idPrefixBase !== '' ? rtrim($idPrefixBase, '_') . '_' : '';
$id = fn(string $name): string => $idPrefixNormalized . $name;

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
$freeformId = $freeform['attributes']['id'] ?? 'tagsInput';
$freeform['attributes']['id'] = $id($freeformId);

$selectionOverrides = (array)($tagSelection ?? []);
$selectedPersonIds = $selectionOverrides['selectedPersonIds'] ?? null;
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

$personService = new PersonService();
$rosterService = new TeamSeasonRosterService();
$selectedGameLabelFromTag = '';
$selectedSiteLabelFromTag = '';
$selectedOpponentLabelFromTag = '';
$selectedRosterLabelFromTag = '';
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
        if ($selectedGameLabelFromTag === '') {
            $selectedGameLabelFromTag = (string)($tag->name ?? $tag['name'] ?? '');
        }
    } elseif (str_starts_with($slug, 'site-')) {
        $selectedSiteId = (int)substr($slug, strlen('site-'));
        if ($selectedSiteLabelFromTag === '') {
            $selectedSiteLabelFromTag = (string)($tag->name ?? $tag['name'] ?? '');
        }
    } elseif (str_starts_with($slug, 'opponent-')) {
        $selectedOpponentId = (int)substr($slug, strlen('opponent-'));
        if ($selectedOpponentLabelFromTag === '') {
            $selectedOpponentLabelFromTag = (string)($tag->name ?? $tag['name'] ?? '');
        }
    } elseif (str_starts_with($slug, 'team-')) {
        $selectedTeamId = (int)substr($slug, strlen('team-'));
    } elseif (str_starts_with($slug, 'sport-')) {
        $selectedSportId = (int)substr($slug, strlen('sport-'));
    } elseif (str_starts_with($slug, 'team_season_roster-')) {
        $selectedRosterId = (int)substr($slug, strlen('team_season_roster-'));
        if ($selectedRosterLabelFromTag === '') {
            $selectedRosterLabelFromTag = (string)($tag->name ?? $tag['name'] ?? '');
        }
        if (!$selectedPersonIds && $selectedRosterId) {
            $rosterData = $rosterService->getRosterDisplayData($selectedRosterId);
            $selectedPersonIds = [(int)($rosterData['person_id'] ?? 0)];
            if ($selectedRosterLabelFromTag === '') {
                $selectedRosterLabelFromTag = (string)($rosterData['team_season_label'] ?? '');
            }
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
$selectedGameLabel = $selectedGameLabel !== '' ? $selectedGameLabel : $selectedGameLabelFromTag;
$selectedSiteLabel = '';
if ($selectedSiteId) {
    foreach ($sites as $s) {
        if ((int)($s['id'] ?? 0) === (int)$selectedSiteId) {
            $selectedSiteLabel = (string)($s['label'] ?? '');
            break;
        }
    }
}
$selectedSiteLabel = $selectedSiteLabel !== '' ? $selectedSiteLabel : $selectedSiteLabelFromTag;
$selectedOpponentLabel = '';
if ($selectedOpponentId) {
    foreach ($opponents as $o) {
        if ((int)($o['id'] ?? 0) === (int)$selectedOpponentId) {
            $selectedOpponentLabel = (string)($o['label'] ?? '');
            break;
        }
    }
}
$selectedOpponentLabel = $selectedOpponentLabel !== '' ? $selectedOpponentLabel : $selectedOpponentLabelFromTag;

$selectedRosterLabel = $selectedRosterLabelFromTag;

$initialPersonCount = count($selectedPersons);
$initialRosterId = (int)($selectedRosterId ?? 0);
$unlockedFields = [
    'person_search',
    'person_select',
    'team_select',
    'teamseason_select',
    'game_search',
    'game_select',
    'site_search',
    'site_select',
    'opponent_search',
    'opponent_select',
    'sport_select',
    'roster_select',
    'tags',
];
foreach ($unlockedFields as $field) {
    $this->Form->unlockField($field);
}
?>

<div data-controller="tag-selection" data-initial-persons-json="<?= h(json_encode($selectedPersons, JSON_THROW_ON_ERROR | JSON_HEX_QUOT)) ?>" data-initial-roster-id="<?= h((int)($initialRosterId ?? 0)) ?>">
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Person</label>
        <div id="<?= h($id('selectedPersons')) ?>" class="d-flex flex-wrap gap-2 mb-2"></div>
        <input
            type="text"
            name="person_search"
            id="<?= h($id('person_search')) ?>"
            list="<?= h($id('personsList')) ?>"
            class="form-control"
            placeholder="Search person by name"
            autocomplete="off"
        />
        <div class="d-flex gap-2 mt-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="<?= h($id('add_person_btn')) ?>">Add Person</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="<?= h($id('clear_persons_btn')) ?>">Clear</button>
        </div>
        <datalist id="<?= h($id('personsList')) ?>"></datalist>
        <div id="<?= h($id('person_hidden_inputs')) ?>">
            <?php foreach ($selectedPersons as $p) : ?>
                <input type="hidden" name="person_select[]" value="<?= h((int)$p['id']) ?>" />
            <?php endforeach; ?>
        </div>
        <div class="form-text">You can tag multiple people. If a roster entry is selected, only one person may be tagged.</div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Team</label>
        <select name="team_select" id="<?= h($id('team_select')) ?>" class="form-select">
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
        <select name="teamseason_select" id="<?= h($id('teamseason_select')) ?>" class="form-select">
            <option value="">-- select team season --</option>
            <?php foreach ($teamSeasons as $ts) : ?>
                <option value="<?= h($ts['id']) ?>" <?= $selectedTeamSeasonId === (int)$ts['id'] ? 'selected' : '' ?>><?= h($ts['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Game</label>
        <div id="<?= h($id('selectedGame')) ?>" class="d-flex flex-wrap gap-2 mb-2"></div>
        <input
            type="text"
            name="game_search"
            id="<?= h($id('game_search')) ?>"
            list="<?= h($id('gamesList')) ?>"
            class="form-control"
            placeholder="Search game"
            autocomplete="off"
            value="<?= h($selectedGameLabel) ?>"
            <?= $selectedTeamSeasonId ? '' : 'disabled' ?>
        />
        <datalist id="<?= h($id('gamesList')) ?>"></datalist>
        <input type="hidden" name="game_select" id="<?= h($id('game_select')) ?>" value="<?= h((int)$selectedGameId) ?>" />
        <div class="form-text">Must select a Team Season first.</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Site</label>
        <input
            type="text"
            name="site_search"
            id="<?= h($id('site_search')) ?>"
            list="<?= h($id('sitesList')) ?>"
            class="form-control"
            placeholder="Search site"
            autocomplete="off"
            value="<?= h($selectedSiteLabel) ?>"
        />
        <datalist id="<?= h($id('sitesList')) ?>"></datalist>
        <input type="hidden" name="site_select" id="<?= h($id('site_select')) ?>" value="<?= h((int)$selectedSiteId) ?>" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Opponent</label>
        <input
            type="text"
            name="opponent_search"
            id="<?= h($id('opponent_search')) ?>"
            list="<?= h($id('opponentsList')) ?>"
            class="form-control"
            placeholder="Search opponent"
            autocomplete="off"
            value="<?= h($selectedOpponentLabel) ?>"
        />
        <datalist id="<?= h($id('opponentsList')) ?>"></datalist>
        <input type="hidden" name="opponent_select" id="<?= h($id('opponent_select')) ?>" value="<?= h((int)$selectedOpponentId) ?>" />
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Sport</label>
        <select name="sport_select" id="<?= h($id('sport_select')) ?>" class="form-select">
            <option value="">-- select sport --</option>
            <?php foreach ($sports as $sp) : ?>
                <option value="<?= h($sp['id']) ?>" <?= $selectedSportId === (int)$sp['id'] ? 'selected' : '' ?>><?= h($sp['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Team Season Roster Entry</label>
        <div id="<?= h($id('selectedRoster')) ?>" class="d-flex flex-wrap gap-2 mb-2" data-initial-label="<?= h($selectedRosterLabel) ?>"></div>
        <select name="roster_select" id="<?= h($id('roster_select')) ?>" class="form-select" <?= $initialPersonCount === 1 ? '' : 'disabled' ?> >
            <option value="">-- select roster entry --</option>
        </select>
        <div class="form-text">Must select a Person first. Other tags cannot be set when a Roster entry is selected.</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="<?= h($freeform['attributes']['id']) ?>"><?= h($freeform['label']) ?></label>
    <?php if ($freeform['type'] === 'textarea') : ?>
        <textarea name="<?= h($freeform['name']) ?>" <?php foreach ($freeform['attributes'] as $attr => $value) :
            ?> <?= h($attr) ?>="<?= h($value) ?>"<?php
                        endforeach; ?>><?= h($freeform['value']) ?></textarea>
    <?php else : ?>
        <input type="<?= h($freeform['type']) ?>" name="<?= h($freeform['name']) ?>" value="<?= h($freeform['value']) ?>" <?php foreach ($freeform['attributes'] as $attr => $value) :
            ?> <?= h($attr) ?>="<?= h($value) ?>"<?php
                     endforeach; ?> />
    <?php endif; ?>
    <?php if (!empty($freeform['help'])) : ?>
        <div class="form-text"><?= h($freeform['help']) ?></div>
    <?php endif; ?>
</div>
</div> <!-- closes data-controller="tag-selection" wrapper -->

<script>
// Direct initialization since Stimulus may not connect in time
document.addEventListener('DOMContentLoaded', function() {
  const wrapper = document.querySelector('[data-controller="tag-selection"]');
  if (!wrapper) return;
  
  const personsJson = wrapper.getAttribute('data-initial-persons-json');
  const rosterId = parseInt(wrapper.getAttribute('data-initial-roster-id') || '0', 10);
  
  if (!personsJson) return;
  
  try {
    const persons = JSON.parse(personsJson);
    const selectedPersonsEl = wrapper.querySelector('#selectedPersons');
    const hiddenInputsContainer = wrapper.querySelector('#person_hidden_inputs');
    const rosterSelect = wrapper.querySelector('#roster_select');
    
    if (!selectedPersonsEl || !hiddenInputsContainer || !rosterSelect) return;
    
    // Clear badges
    selectedPersonsEl.innerHTML = '';
    
    // Render persons
    persons.forEach(person => {
      const badge = document.createElement('span');
      badge.className = 'badge bg-secondary';
      badge.textContent = person.label;
      selectedPersonsEl.appendChild(badge);
      
      // Fetch and populate rosters
      if (rosterId > 0) {
        fetch(`/admin/tag-lookups/rosters?person_id=${person.id}`)
          .then(r => r.json())
          .then(data => {
            // Clear existing options
            while (rosterSelect.options.length > 1) {
              rosterSelect.remove(1);
            }
            
            // Add new options
            if (data.rosters && Array.isArray(data.rosters)) {
              data.rosters.forEach(roster => {
                const option = document.createElement('option');
                option.value = roster.id;
                option.textContent = roster.label;
                if (roster.id === rosterId) {
                  option.selected = true;
                }
                rosterSelect.appendChild(option);
              });
            }
          });
      }
    });
  } catch (e) {
    console.error('Tag selection initialization error:', e);
  }
});
</script>

<?php
// Script removed - Stimulus tag_selection_controller now handles all initialization
// The controller is instantiated via data-controller="tag-selection" on the wrapper div above
// Initial data is passed via data- attributes
?>
<!-- Direct initialization script handles tag selection setup -->
