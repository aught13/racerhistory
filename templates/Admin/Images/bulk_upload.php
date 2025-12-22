<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="container py-4">
    <h1 class="mb-3">Bulk Upload Images</h1>
    <p class="text-muted mb-4">Select multiple images, then apply shared tags to all of them.</p>

    <?= $this->Form->create(null, ['type' => 'file', 'id' => 'bulkUploadForm']) ?>
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Select Files</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="uploads" class="form-label">Image files</label>
                <input type="file" class="form-control" id="uploads" name="uploads[]" accept="image/*" multiple aria-describedby="uploadsHelp">
                <div id="uploadsHelp" class="form-text">You can pick multiple files; supported types: JPG, PNG, GIF, WebP.</div>
            </div>

            <div id="fileList" class="row g-3"></div>

            <div class="d-flex gap-2 mt-3">
                <button id="uploadAll" class="btn btn-primary" type="button" disabled>
                    <span class="label">Upload Selected</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <a class="btn btn-outline-secondary" href="<?= $this->Url->build(['action' => 'index']) ?>">Back to Images</a>
            </div>

            <div id="uploadStatus" class="mt-3"></div>
        </div>
    </div>

    <!-- Entity Tags (Apply to All Files) -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Entity Tags (Apply to All Files)</h5>
        </div>
        <div class="card-body">
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
                    />
                    <datalist id="personsList"></datalist>
                    <input type="hidden" name="person_select" id="person_select" value="" />
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Team</label>
                    <select name="team_select" id="team_select" class="form-select">
                        <option value="">-- select team --</option>
                        <?php if (isset($teams)): ?>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= h($t->id) ?>"><?= h($t->team_name) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Team Season</label>
                    <select name="teamseason_select" id="teamseason_select" class="form-select">
                        <option value="">-- select team season --</option>
                        <?php if (isset($teamSeasonLabels)): ?>
                            <?php foreach ($teamSeasonLabels as $ts): ?>
                                <option value="<?= h($ts['id']) ?>"><?= h($ts['label']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Game</label>
                    <select name="game_select" id="game_select" class="form-select">
                        <option value="">-- select game --</option>
                        <?php if (isset($gameLabels)): ?>
                            <?php foreach ($gameLabels as $g): ?>
                                <option value="<?= h($g['id']) ?>" data-teamseason="<?= h($g['team_season_id'] ?? '') ?>"><?= h($g['label']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">Tip: Select a Team Season first to filter games</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Site</label>
                    <select name="site_select" id="site_select" class="form-select">
                        <option value="">-- select site --</option>
                        <?php if (isset($siteLabels)): ?>
                            <?php foreach ($siteLabels as $s): ?>
                                <option value="<?= h($s['id']) ?>"><?= h($s['label']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Opponent</label>
                    <select name="opponent_select" id="opponent_select" class="form-select">
                        <option value="">-- select opponent --</option>
                        <?php if (isset($opponents)): ?>
                            <?php foreach ($opponents as $o): ?>
                                <option value="<?= h($o['id']) ?>"><?= h($o['label']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sport</label>
                    <select name="sport_select" id="sport_select" class="form-select">
                        <option value="">-- select sport --</option>
                        <?php if (isset($sports)): ?>
                            <?php foreach ($sports as $sp): ?>
                                <option value="<?= h($sp->id) ?>"><?= h($sp->sport_name) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Team Season Roster Entry</label>
                    <select name="roster_select" id="roster_select" class="form-select" disabled>
                        <option value="">-- select roster entry --</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="commonTags" class="form-label">Additional Freeform Tags (comma-separated)</label>
                <input type="text" class="form-control" id="commonTags" placeholder="tag1, tag2, tag3">
                <div class="form-text">These will be applied to all uploaded files along with entity tags.</div>
            </div>

            <div class="alert alert-info small">
                <strong>Note:</strong> Entity tags and freeform tags here apply to <strong>all</strong> uploaded files.
            </div>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<script>
const uploadsInput = document.getElementById('uploads');
const fileList = document.getElementById('fileList');
const uploadBtn = document.getElementById('uploadAll');
const statusBox = document.getElementById('uploadStatus');
const csrfToken = document.querySelector('input[name="_csrfToken"]')?.value;

// Entity tag references
const personSearch = document.getElementById('person_search');
const personSelect = document.getElementById('person_select');
const personsList = document.getElementById('personsList');
const teamSeasonSelect = document.getElementById('teamseason_select');
const gameSelect = document.getElementById('game_select');
const rosterSelect = document.getElementById('roster_select');
const commonTagsInput = document.getElementById('commonTags');

// Store game options for filtering by team season
let allGameOptions = [];
if (gameSelect) {
    allGameOptions = Array.from(gameSelect.options).slice(1);
}

function filterGamesByTeamSeason() {
    if (!gameSelect || !teamSeasonSelect) return;
    const selectedTeamSeason = teamSeasonSelect.value;
    gameSelect.innerHTML = '<option value="">-- select game --</option>';
    if (!selectedTeamSeason) {
        allGameOptions.forEach(opt => gameSelect.appendChild(opt.cloneNode(true)));
    } else {
        allGameOptions.forEach(opt => {
            if (opt.getAttribute('data-teamseason') === selectedTeamSeason) {
                gameSelect.appendChild(opt.cloneNode(true));
            }
        });
    }
}

if (teamSeasonSelect) {
    teamSeasonSelect.addEventListener('change', filterGamesByTeamSeason);
}

// Person search functionality (debounced)
let lastPersons = [];

function renderPersonDatalist(persons) {
    if (!personsList) return;
    personsList.innerHTML = '';
    persons.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.label;
        personsList.appendChild(opt);
    });
}

function fetchPersons(query) {
    if (!query || query.trim() === '') {
        lastPersons = [];
        renderPersonDatalist([]);
        return Promise.resolve();
    }
    return fetch('/admin/images/persons?q=' + encodeURIComponent(query), {credentials: 'same-origin'})
        .then(r => r.json())
        .then(data => {
            lastPersons = (data?.persons) || [];
            renderPersonDatalist(lastPersons);
        })
        .catch(() => {
            lastPersons = [];
            renderPersonDatalist([]);
        });
}

function debounce(fn, ms) {
    let t = null;
    return function (...args) {
        if (t) clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), ms);
    };
}

function populateRostersForPerson(personId) {
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
            if (data?.rosters && Array.isArray(data.rosters)) {
                data.rosters.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.label;
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

if (personSearch) {
    const debouncedFetch = debounce((val) => {
        fetchPersons(val || '').then(() => {
            // Try to match against last results
            if (!personSelect?.value && val) {
                const found = lastPersons.find(p => p.label.toLowerCase() === val.toLowerCase());
                if (found) {
                    personSelect.value = found.id;
                }
            }
            populateRostersForPerson(personSelect?.value || '');
        });
    }, 250);

    personSearch.addEventListener('input', (e) => {
        debouncedFetch((e.target.value || '').trim());
    });
}


function renderFileRows(files) {
    fileList.innerHTML = '';
    if (!files.length) {
        uploadBtn.setAttribute('disabled', 'disabled');
        return;
    }
    uploadBtn.removeAttribute('disabled');

    Array.from(files).forEach((file, index) => {
        const col = document.createElement('div');
        col.className = 'col-12';
        col.innerHTML = `
            <div class="border rounded p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div>
                        <strong>${file.name}</strong>
                        <div class="text-muted small">${Math.round(file.size / 1024)} KB</div>
                    </div>
                    <span class="badge bg-secondary">#${index + 1}</span>
                </div>
                <div class="text-muted small">Tags will be applied from the "Entity Tags (Apply to All Files)" section above.</div>
            </div>
        `;
        fileList.appendChild(col);
    });
}

uploadsInput?.addEventListener('change', (e) => {
    renderFileRows(e.target.files || []);
});

function showStatus(type, message, details = '') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<div class="fw-semibold mb-1">${message}</div>${details ? `<div class="small">${details}</div>` : ''}`;
    statusBox.innerHTML = '';
    statusBox.appendChild(alert);
}

uploadBtn?.addEventListener('click', async () => {
    const files = uploadsInput.files;
    if (!files || !files.length) {
        showStatus('danger', 'Please choose at least one image file.');
        return;
    }

    uploadBtn.querySelector('.label').classList.add('d-none');
    uploadBtn.querySelector('.spinner-border').classList.remove('d-none');
    uploadBtn.setAttribute('disabled', 'disabled');
    statusBox.innerHTML = '';

    const formData = new FormData();
    if (csrfToken) {
        formData.append('_csrfToken', csrfToken);
    }
    Array.from(files).forEach((file, index) => {
        formData.append('uploads[' + index + ']', file);
    });

    // Add common entity tags to all files
    if (personSelect?.value) formData.append('person_select', personSelect.value);
    if (teamSeasonSelect?.value) formData.append('teamseason_select', teamSeasonSelect.value);
    if (gameSelect?.value) formData.append('game_select', gameSelect.value);
    if (document.getElementById('team_select')?.value) formData.append('team_select', document.getElementById('team_select').value);
    if (document.getElementById('site_select')?.value) formData.append('site_select', document.getElementById('site_select').value);
    if (document.getElementById('opponent_select')?.value) formData.append('opponent_select', document.getElementById('opponent_select').value);
    if (document.getElementById('sport_select')?.value) formData.append('sport_select', document.getElementById('sport_select').value);
    if (rosterSelect?.value) formData.append('roster_select', rosterSelect.value);
    if (commonTagsInput?.value) formData.append('common_tags', commonTagsInput.value);

    try {
        const response = await fetch('/admin/images/bulk-upload', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text.substring(0, 500));
            showStatus('danger', 'Server returned invalid response (expected JSON, got HTML). Check console for details.');
            return;
        }

        const data = await response.json();
        const successes = (data.results || []).filter(r => r.success);
        const failures = (data.results || []).filter(r => !r.success);

        let detailHtml = '<ul class="mb-0 ps-3">';
        (data.results || []).forEach(r => {
            const status = r.success ? 'text-success' : 'text-danger';
            const icon = r.success ? 'bi-check-circle' : 'bi-exclamation-circle';
            detailHtml += `<li class="${status}"><i class="bi ${icon}"></i> ${r.name || 'unnamed'}${r.existing ? ' (duplicate)' : ''}${r.error ? ': ' + r.error : ''}</li>`;
        });
        detailHtml += '</ul>';

        if (successes.length && failures.length === 0) {
            showStatus('success', 'All images uploaded successfully.', detailHtml);
        } else if (successes.length && failures.length) {
            showStatus('warning', 'Some images uploaded; some failed.', detailHtml);
        } else {
            showStatus('danger', 'Upload failed.', detailHtml);
        }
    } catch (err) {
        showStatus('danger', 'Unexpected error while uploading.', err?.message || '');
    } finally {
        uploadBtn.querySelector('.label').classList.remove('d-none');
        uploadBtn.querySelector('.spinner-border').classList.add('d-none');
        uploadBtn.removeAttribute('disabled');
    }
});
</script>
