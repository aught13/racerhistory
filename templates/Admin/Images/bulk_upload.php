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
            <?php
            $sportsForSelect = [];
            if (isset($sports) && is_iterable($sports)) {
                foreach ($sports as $sp) {
                    $sportsForSelect[] = ['id' => $sp->id ?? null, 'label' => $sp->sport_name ?? ''];
                }
            }
            ?>
            <?= $this->element('Admin/tag_selection', [
                'teams' => $teams ?? [],
                'teamSeasons' => $teamSeasonLabels ?? [],
                'games' => $gameLabels ?? [],
                'sites' => $siteLabels ?? [],
                'opponents' => $opponents ?? [],
                'sports' => $sportsForSelect,
                'currentTags' => $currentTags ?? [],
                'tagString' => '',
                'freeform' => [
                    'type' => 'text',
                    'name' => 'common_tags',
                    'label' => 'Additional Freeform Tags (comma-separated)',
                    'help' => 'These will be applied to all uploaded files along with entity tags.',
                    'attributes' => [
                        'id' => 'commonTags',
                        'placeholder' => 'tag1, tag2, tag3',
                    ],
                ],
            ]) ?>
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
const personSelect = document.getElementById('person_select');
const teamSeasonSelect = document.getElementById('teamseason_select');
const gameSelect = document.getElementById('game_select');
const rosterSelect = document.getElementById('roster_select');
const commonTagsInput = document.getElementById('commonTags');
const teamSelect = document.getElementById('team_select');
const siteSelect = document.getElementById('site_select');
const opponentSelect = document.getElementById('opponent_select');
const sportSelect = document.getElementById('sport_select');


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
    if (teamSelect?.value) formData.append('team_select', teamSelect.value);
    if (siteSelect?.value) formData.append('site_select', siteSelect.value);
    if (opponentSelect?.value) formData.append('opponent_select', opponentSelect.value);
    if (sportSelect?.value) formData.append('sport_select', sportSelect.value);
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
