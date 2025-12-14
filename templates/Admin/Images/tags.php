<?php
$this->assign('title', 'Manage Image Tags');
?>
<div class="container py-4">
  <h1 class="mb-4">Manage Tags - Image #<?= h($image->id) ?></h1>

  <div class="row g-4">
    <!-- Image Preview -->
    <div class="col-md-4">
      <?php $serveUrl = $this->ImageServe->urlForImage($image); ?>
      <figure>
        <img src="<?= h($serveUrl) ?>" alt="Preview" class="img-fluid rounded border" />
        <figcaption class="mt-2 small text-muted">
          <strong><?= h($image->original_name) ?></strong><br>
          <?= h($image->width) ?>×<?= h($image->height) ?> • <?= h($image->byte_size) ?> bytes
        </figcaption>
      </figure>
    </div>

    <!-- Tag Management Form -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-light">
          <h5 class="mb-0">Current Tags</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($currentTags)): ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
              <?php foreach ($currentTags as $tag): ?>
                <span class="badge bg-info text-dark"><?= h($tag->name) ?></span>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-warning mb-4" role="alert">
              <strong>No tags assigned.</strong> Add tags below to organize this image.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Edit Tags Form -->
      <div class="card mt-4">
        <div class="card-header bg-light">
          <h5 class="mb-0">Edit Tags</h5>
        </div>
        <div class="card-body">
          <?= $this->Form->create(null, ['url' => ['action' => 'tags', $image->id]]) ?>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Person</label>
                  <?php
                    // Build person name list for JS autocomplete mapping
                    $personItems = [];
                    foreach ($persons as $p) {
                        $name = trim(($p->first ?? '') . ' ' . ($p->last ?? '')) ?: ('#' . $p->id);
                        $personItems[] = ['id' => $p->id, 'name' => $name];
                    }
                    $personsJson = json_encode($personItems);
                  ?>

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
                  <input type="hidden" name="person_select" id="person_select" value="<?= h($selectedPersonId ?? '') ?>" />
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Team</label>
                  <select name="team_select" class="form-select">
                    <option value="">-- select team --</option>
                    <?php foreach ($teams as $t): ?>
                      <option value="<?= h($t->id) ?>"><?= h($t->team_name) ?> (<?= h($t->id) ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Team Season</label>
                  <select name="teamseason_select" id="teamseason_select" class="form-select">
                    <option value="">-- select team season --</option>
                    <?php foreach ($teamSeasons as $ts): ?>
                      <option value="<?= h($ts['id']) ?>"><?= h($ts['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Game</label>
                  <select name="game_select" id="game_select" class="form-select">
                    <option value="">-- select game --</option>
                    <?php foreach ($games as $g): ?>
                      <option value="<?= h($g['id']) ?>" data-teamseason="<?= h($g['team_season_id'] ?? '') ?>"><?= h($g['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="form-text">Tip: Select a Team Season first to filter games</div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Site</label>
                  <select name="site_select" class="form-select">
                    <option value="">-- select site --</option>
                    <?php foreach ($sites as $s): ?>
                      <option value="<?= h($s['id']) ?>"><?= h($s['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Opponent</label>
                  <select name="opponent_select" class="form-select">
                    <option value="">-- select opponent --</option>
                    <?php foreach ($opponents as $o): ?>
                      <option value="<?= h($o['id']) ?>"><?= h($o['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Sport</label>
                  <select name="sport_select" class="form-select">
                    <option value="">-- select sport --</option>
                    <?php foreach ($sports as $sp): ?>
                      <option value="<?= h($sp->id) ?>"><?= h($sp->sport_name) ?> (<?= h($sp->id) ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Team Season Roster Entry</label>
                  <select name="roster_select" id="roster_select" class="form-select" <?= empty($selectedPersonId) ? 'disabled' : '' ?> >
                    <option value="">-- select roster entry --</option>
                    <?php if (!empty($selectedPersonId) && !empty($rosters)): ?>
                      <?php foreach ($rosters as $r): ?>
                        <?php if ((int)$r['person_id'] !== (int)$selectedPersonId) continue; ?>
                        <option value="<?= h($r['id']) ?>" <?= isset($selectedRosterId) && $selectedRosterId == $r['id'] ? 'selected' : '' ?>><?= h($r['label']) ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label for="tagsInput" class="form-label">Additional Tags (comma-separated)</label>
                <textarea class="form-control" id="tagsInput" name="tags" rows="3"><?= h($tagString) ?></textarea>
                <div class="form-text">Freeform tags will be included along with entity tags.</div>
              </div>

              <div class="d-flex gap-2">
                <?= $this->Form->button('Update Tags', ['class' => 'btn btn-primary']) ?>
                <?= $this->Html->link('Cancel', ['action' => 'edit', $image->id], ['class' => 'btn btn-secondary']) ?>
              </div>

          <?= $this->Form->end() ?>

          <script>
            (function () {
              let lastPersons = [];
              const list = document.getElementById('personsList');
              const search = document.getElementById('person_search');
              const hidden = document.getElementById('person_select');
              const rosterSelect = document.getElementById('roster_select');
              const teamSeasonSelect = document.getElementById('teamseason_select');
              const gameSelect = document.getElementById('game_select');
              const selectedRosterId = <?= isset($selectedRosterId) ? (int)$selectedRosterId : 0 ?>;

              // Store all game options for filtering
              const allGameOptions = gameSelect ? Array.from(gameSelect.options).slice(1) : []; // Skip first "-- select --" option

              // Filter games by selected team season
              function filterGamesByTeamSeason() {
                if (!gameSelect || !teamSeasonSelect) return;

                const selectedTeamSeason = teamSeasonSelect.value;

                // Clear current options (keep first "-- select --")
                gameSelect.innerHTML = '<option value="">-- select game --</option>';

                if (!selectedTeamSeason) {
                  // No filter - show all games
                  allGameOptions.forEach(opt => gameSelect.appendChild(opt.cloneNode(true)));
                } else {
                  // Filter by team_season_id
                  allGameOptions.forEach(opt => {
                    if (opt.getAttribute('data-teamseason') === selectedTeamSeason) {
                      gameSelect.appendChild(opt.cloneNode(true));
                    }
                  });
                }
              }

              // Populate datalist from remote search results
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
                if (!name) {
                  hidden.value = '';
                  return;
                }
                const found = lastPersons.find(p => p.label === name);
                if (found) {
                  hidden.value = found.id;
                } else {
                  hidden.value = '';
                }
              }

              // Fetch persons from server (debounced wrapper will call this)
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

              // Simple debounce helper
              function debounce(fn, ms) {
                let t = null;
                return function (...args) {
                  if (t) clearTimeout(t);
                  t = setTimeout(() => fn.apply(this, args), ms);
                };
              }

              function populateRostersForPerson(personId, preselectId) {
                if (!personId) {
                  // disable and clear
                  if (rosterSelect) {
                    rosterSelect.innerHTML = '<option value="">-- select roster entry --</option>';
                    rosterSelect.disabled = true;
                  }
                  return;
                }
                fetch('/admin/images/rosters?person_id=' + encodeURIComponent(personId), {credentials: 'same-origin'})
                  .then(r => r.json())
                  .then(data => {
                    if (!rosterSelect) return;
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
                    if (rosterSelect) {
                      rosterSelect.innerHTML = '<option value="">-- select roster entry --</option>';
                      rosterSelect.disabled = true;
                    }
                  });
              }

              if (search) {
                const debouncedFetch = debounce((val) => {
                  fetchPersons(val || '').then(() => {
                    setHiddenFromName(val || '');
                    const pid = hidden.value || '';
                    populateRostersForPerson(pid, selectedRosterId);
                  });
                }, 250);

                search.addEventListener('input', (e) => {
                  const val = e.target.value.trim();
                  debouncedFetch(val);
                });
                const form = search.closest('form');
                if (form) {
                  form.addEventListener('submit', (e) => {
                    // Try best-effort matching using last fetched results: exact then startsWith (case-insensitive)
                    if (!hidden.value) {
                      const val = (search.value || '').trim();
                      if (!val) return;
                      let found = lastPersons.find(p => p.label.toLowerCase() === val.toLowerCase());
                      if (!found) {
                        found = lastPersons.find(p => p.label.toLowerCase().startsWith(val.toLowerCase()));
                      }
                      hidden.value = found ? found.id : '';
                    }
                  });
                }
              }

              // Team season change event - filter games
              if (teamSeasonSelect) {
                teamSeasonSelect.addEventListener('change', filterGamesByTeamSeason);
              }

              // Initial population on load if a person is already selected
              (function init() {
                const pid = hidden.value || '';
                if (pid) {
                  populateRostersForPerson(pid, selectedRosterId || 0);
                } else {
                  if (rosterSelect) rosterSelect.disabled = true;
                }
              })();
            })();
          </script>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function prependTag(tag) {
  const textarea = document.getElementById('tagsInput');
  const currentValue = textarea.value.trim();
  if (currentValue) {
    textarea.value = currentValue + ', ' + tag;
  } else {
    textarea.value = tag;
  }
  textarea.focus();
}
</script>
