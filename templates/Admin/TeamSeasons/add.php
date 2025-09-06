<?php

$this->assign('title', 'Add Team Season'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team Seasons</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Add Team Season</li>
                </ol>
            </nav>
            <h1 class="mb-3">Add New Team Season</h1>
            <p class="text-muted">
                Create a new team season linking a team to a specific season with competition details.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Team Season Information</h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($teamSeason, ['novalidate' => true]) ?>

                    <div class="mb-3">
                        <label for="team-id" class="form-label">Team *</label>
                        <div class="input-group d-flex align-items-stretch">
                            <div class="flex-grow-1">
                                <?= $this->Form->control('team_id', [
                                    'type' => 'select',
                                    'options' => $teams,
                                    'empty' => 'Select a Team',
                                    'class' => 'form-select h-100',
                                    'label' => false,
                                    'required' => true,
                                    'id' => 'team-id'
                                ]) ?>
                            </div>
                            <button
                                type="button"
                                class="btn btn-success h-100 border-0"
                                data-bs-toggle="modal"
                                data-bs-target="#add-team-modal"
                                title="Add New Team"
                                aria-label="Add new team">
                                <i class="bi bi-plus-circle"></i>
                            </button>
                        </div>
                        <div class="form-text">Select the team for this season participation.</div>
                    </div>

                    <div class="mb-3">
                        <label for="season-id" class="form-label">Season *</label>
                        <div class="input-group d-flex align-items-stretch">
                            <div class="flex-grow-1">
                                <?= $this->Form->control('season_id', [
                                    'type' => 'select',
                                    'options' => $seasonsList,
                                    'empty' => 'Select a Season',
                                    'class' => 'form-select h-100',
                                    'label' => false,
                                    'required' => true,
                                    'id' => 'season-id'
                                ]) ?>
                            </div>
                            <button
                                type="button"
                                class="btn btn-success h-100 border-0"
                                data-bs-toggle="modal"
                                data-bs-target="#add-season-modal"
                                title="Add New Season"
                                aria-label="Add new season">
                                <i class="bi bi-plus-circle"></i>
                            </button>
                        </div>
                        <div class="form-text">Select the season for this team participation.</div>
                    </div>

                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester *</label>
                        <?= $this->Form->control('semester', [
                            'type' => 'number',
                            'class' => 'form-control',
                            'label' => false,
                            'required' => true,
                            'id' => 'semester',
                            'min' => 1,
                            'max' => 4,
                            'placeholder' => 'e.g., 1'
                        ]) ?>
                        <div class="form-text">Semester number (1-4) for this team season.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="league" class="form-label">League</label>
                                <?= $this->Form->control('league', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league',
                                    'maxlength' => 240,
                                    'placeholder' => 'e.g., NCAA Division I'
                                ]) ?>
                                <div class="form-text">League or conference name (max 240 characters).</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="league-abbr" class="form-label">League Abbreviation</label>
                                <?= $this->Form->control('league_abbr', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league-abbr',
                                    'maxlength' => 10,
                                    'placeholder' => 'e.g., NCAA'
                                ]) ?>
                                <div class="form-text">League abbreviation (max 10 characters).</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="league-finish" class="form-label">League Finish</label>
                                <?= $this->Form->control('league_finish', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league-finish',
                                    'maxlength' => 240,
                                    'placeholder' => 'e.g., 1st Place, 3-5'
                                ]) ?>
                                <div class="form-text">Final position or record in league play.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="league-tournament-finish" class="form-label">League Tournament Finish</label>
                                <?= $this->Form->control('league_torunament_finish', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'league-tournament-finish',
                                    'maxlength' => 240,
                                    'placeholder' => 'e.g., Champion, Semi-Finals'
                                ]) ?>
                                <div class="form-text">Tournament finish position.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="last-post-game" class="form-label">Last Post Game</label>
                        <?= $this->Form->control('last_post_game', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'last-post-game',
                            'maxlength' => 240,
                            'placeholder' => 'Final game or post-season information'
                        ]) ?>
                        <div class="form-text">Information about the final game or post-season activities.</div>
                    </div>

                    <div class="mb-3">
                        <label for="team-season-notes" class="form-label">Season Notes</label>
                        <?= $this->Form->control('team_season_notes', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'team-season-notes',
                            'maxlength' => 240,
                            'placeholder' => 'Additional notes about this season'
                        ]) ?>
                        <div class="form-text">General notes about this team season (max 240 characters).</div>
                    </div>

                    <div class="mb-3">
                        <label for="team-season-image" class="form-label">Season Image (ID)</label>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <?= $this->Form->control('team_season_image', [
                                    'type' => 'text',
                                    'class' => 'form-control',
                                    'label' => false,
                                    'id' => 'team-season-image',
                                    'maxlength' => 162,
                                    'placeholder' => 'Numeric image id after upload'
                                ]) ?>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-secondary w-100" id="select-team-season-image">Select / Upload</button>
                            </div>
                        </div>
                        <div id="team-season-image-preview" class="mt-2" style="display:none;">
                            <img src="" alt="Season Image Preview" class="img-thumbnail" style="max-height:150px;">
                        </div>
                        <div class="form-text">Upload an image; its numeric ID will be stored.</div>
                    </div>

                    <div class="mb-3">
                        <label for="team-season-preview" class="form-label">Season Preview</label>
                        <?= $this->Form->control('team_season_preview', [
                            'type' => 'textarea',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'team-season-preview',
                            'rows' => 8,
                            'placeholder' => 'Preview text for the upcoming season...',
                            'templates' => [
                                'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>'
                            ]
                        ]) ?>
                        <div class="form-text">Pre-season preview or expectations. Rich text supported.</div>
                    </div>

                    <div class="mb-3">
                        <label for="team-season-recap" class="form-label">Season Recap</label>
                        <?= $this->Form->control('team_season_recap', [
                            'type' => 'textarea',
                            'class' => 'form-control',
                            'label' => false,
                            'id' => 'team-season-recap',
                            'rows' => 8,
                            'placeholder' => 'Summary of the completed season...',
                            'templates' => [
                                'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>'
                            ]
                        ]) ?>
                        <div class="form-text">Post-season recap or summary. Rich text supported.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button(__('Save Team Season'), [
                            'type' => 'submit',
                            'class' => 'btn btn-success'
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Team Season Guidelines</h4>
                </div>
                <div class="card-body">
                    <h5>Required Fields</h5>
                    <ul class="small text-muted">
                        <li>Team - Must select an existing team</li>
                        <li>Season - Must select an existing season</li>
                        <li>Semester - Number from 1-4</li>
                    </ul>

                    <h5>League Information</h5>
                    <p class="small text-muted">
                        League fields are optional but help track competitive context and results.
                    </p>

                    <h5>Text Fields</h5>
                    <ul class="small text-muted">
                        <li>Preview - Pre-season expectations</li>
                        <li>Recap - Post-season summary</li>
                        <li>Notes - General information</li>
                    </ul>

                    <h5>Tips</h5>
                    <ul class="small text-muted">
                        <li>Use consistent league naming</li>
                        <li>Include relevant statistics in recap</li>
                        <li>Add new teams/seasons if needed</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden forms to generate FormProtection tokens for AJAX requests -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'ajaxAdd'],
        'id' => 'hidden-team-form'
    ]) ?>
    <?= $this->Form->control('sport_id', ['type' => 'select']) ?>
    <?= $this->Form->control('team_name', ['type' => 'text']) ?>
    <?= $this->Form->control('abbr', ['type' => 'text']) ?>
    <?= $this->Form->control('gender', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'ajaxAdd'],
        'id' => 'hidden-season-form'
    ]) ?>
    <?= $this->Form->control('start', ['type' => 'text']) ?>
    <?= $this->Form->control('end', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-team-modal',
    'title' => 'Add New Team',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'ajaxAdd']),
    'targetSelectId' => 'team-id',
    'hiddenFormId' => 'hidden-team-form',
    'fields' => [
        [
            'name' => 'sport_id',
            'type' => 'select',
            'label' => 'Sport',
            'required' => true,
            'options' => $sports ?? []
        ],
        [
            'name' => 'team_name',
            'type' => 'text',
            'label' => 'Team Name',
            'required' => true
        ],
        [
            'name' => 'abbr',
            'type' => 'text',
            'label' => 'Abbreviation',
            'required' => true
        ],
        [
            'name' => 'gender',
            'type' => 'select',
            'label' => 'Gender',
            'required' => true,
            'options' => ['M' => 'Male', 'F' => 'Female', 'C' => 'Co-ed']
        ]
    ]
]) ?>

<?php
echo $this->Html->script('/js/tinymce/tinymce.min.js?v=1', ['block' => true]);
echo $this->Html->scriptBlock(<<<JS
document.addEventListener('DOMContentLoaded', function(){
    function initEditor(id){
        if (!document.getElementById(id) || typeof tinymce === 'undefined') return;
        tinymce.init({
            license_key: 'gpl',
            selector: '#' + id,
            menubar: false,
            plugins: 'image code lists advlist media preview quickbars save visualblocks visualchars',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | image media | code preview',
            quickbars_selection_toolbar: 'bold italic underline | quicklink blockquote | bullist numlist',
            image_title: true,
            automatic_uploads: true,
            images_upload_url: '/admin/images/upload',
            images_upload_credentials: true,
            convert_urls: false,
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/admin/images/upload');
                        xhr.withCredentials = true;
                        xhr.upload.onprogress = function (e) { if (e.lengthComputable) { progress(e.loaded / e.total * 100); } };
                        xhr.onload = function () {
                                if (xhr.status < 200 || xhr.status >= 300) { return reject('HTTP Error: ' + xhr.status); }
                                var raw = xhr.responseText; var json;
                                try { json = JSON.parse(raw); } catch(err){ return reject('Invalid JSON'); }
                                if (!json.success || !json.image || !json.image.url) { return reject(json.error || 'Upload failed'); }
                                resolve(json.image.url);
                        };
                        xhr.onerror = function(){ reject('Image upload failed'); };
                        var formData = new FormData();
                        formData.append('upload', blobInfo.blob(), blobInfo.filename());
                        var csrf = document.querySelector('meta[name="csrfToken"]');
                        if (csrf) xhr.setRequestHeader('X-CSRF-Token', csrf.getAttribute('content'));
                        xhr.send(formData);
                });
            }
        });
    }
    initEditor('team-season-preview');
    initEditor('team-season-recap');

    const btn = document.getElementById('select-team-season-image');
    const field = document.getElementById('team-season-image');
    const previewWrap = document.getElementById('team-season-image-preview');
    if (btn && field) {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const input = document.createElement('input');
            input.type = 'file'; input.accept = 'image/*';
            input.onchange = function(){
                if (!input.files || !input.files[0]) return;
                const file = input.files[0];
                const formData = new FormData(); formData.append('upload', file);
                btn.disabled = true; btn.textContent = 'Uploading...';
                fetch('/admin/images/upload', { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrfToken"]').getAttribute('content') } })
                .then(r => r.json())
                .then(data => { if (!data.success || !data.image) { alert('Upload failed: ' + (data.error || 'Unknown error')); return; } field.value = data.image.id; const img = previewWrap.querySelector('img'); img.src = data.image.url; previewWrap.style.display = 'block'; })
                .catch(err => { console.error(err); alert('Upload failed: ' + err.message); })
                .finally(()=>{ btn.disabled = false; btn.textContent = 'Select / Upload'; });
            };
            input.click();
        });
        if (field.value && !isNaN(parseInt(field.value))) { const img = previewWrap.querySelector('img'); img.src = '/images/serve/' + field.value; previewWrap.style.display = 'block'; }
    }
});
JS, ['block' => true]);
?>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-season-modal',
    'title' => 'Add New Season',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'ajaxAdd']),
    'targetSelectId' => 'season-id',
    'hiddenFormId' => 'hidden-season-form',
    'fields' => [
        [
            'name' => 'start',
            'type' => 'text',
            'label' => 'Start Year',
            'required' => true
        ],
        [
            'name' => 'end',
            'type' => 'text',
            'label' => 'End Year',
            'required' => true
        ]
    ]
]) ?>
