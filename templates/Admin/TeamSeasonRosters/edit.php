<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TeamSeasonRoster $teamSeasonRoster
 * @var \Cake\Collection\CollectionInterface|string[] $teamSeasonsList
 * @var \Cake\Collection\CollectionInterface|string[] $persons
 */
$this->assign('title', 'Edit Team Season Roster');
?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team
                            Seasons</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonRoster->team_season_id]) ?>">Team
                            Season View</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Roster</li>
                </ol>
            </nav>
            <h1 class="mb-3">Edit Team Season Roster</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-body">
                    <?= $this->Form->create($teamSeasonRoster, ['id' => 'main-roster-form']) ?>
                    <fieldset>
                        <?php
                        echo $this->Form->control('team_season_id', [
                            'options' => $teamSeasonsList,
                            'class' => 'form-select',
                            'label' => 'Team Season'
                        ]);
                        echo $this->Form->control('person_id', [
                            'empty' => '(Start typing to search people...)',
                            'options' => [],
                            'class' => 'form-select',
                            'label' => 'Person',
                            'id' => 'person-id-select',
                            'data-dynamic-person' => '1'
                        ]);
                        echo '<small class="text-muted">Type at least 2 characters to search by display / first / last name.</small>';
                        ?>
                        <div class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                data-bs-target="#add-person-modal">
                                <i class="bi bi-plus-circle"></i> Add New Person
                            </button>
                        </div>
                        <?php
                        echo $this->Form->control('roster_year', ['class' => 'form-control', 'label' => 'Year']);
                        echo $this->Form->control('roster_number', ['class' => 'form-control', 'label' => 'Number']);
                        echo $this->Form->control('roster_position', ['class' => 'form-control', 'label' => 'Position']);
                        echo $this->Form->control('roster_height', ['class' => 'form-control', 'label' => 'Height']);
                        echo $this->Form->control('roster_weight', ['class' => 'form-control', 'label' => 'Weight']);
                        ?>
                    </fieldset>
                    <div class="mt-3">
                        <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary']) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonRoster->team_season_id]) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$personFields = [
    ['name' => 'first', 'label' => 'First Name', 'type' => 'text'],
    ['name' => 'last', 'label' => 'Last Name', 'type' => 'text'],
    ['name' => 'full', 'label' => 'Full Name', 'type' => 'text'],
    ['name' => 'display', 'label' => 'Display Name', 'required' => true, 'type' => 'text'],
    ['name' => 'birth', 'label' => 'Birth Date', 'type' => 'date'],
    ['name' => 'death', 'label' => 'Death Date', 'type' => 'date'],
    ['name' => 'person_previous', 'label' => 'Previous School', 'type' => 'text'],
    ['name' => 'birth_place_id', 'label' => 'Birth Place', 'type' => 'hidden'],
];
?>

<!-- Hidden form for FormProtection tokens (person ajaxAdd endpoint) -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxAdd'],
        'id' => 'hidden-person-form',
    ]) ?>
    <?= $this->Form->control('first', ['type' => 'text']) ?>
    <?= $this->Form->control('last', ['type' => 'text']) ?>
    <?= $this->Form->control('full', ['type' => 'text']) ?>
    <?= $this->Form->control('display', ['type' => 'text']) ?>
    <?= $this->Form->control('birth', ['type' => 'text']) ?>
    <?= $this->Form->control('death', ['type' => 'text']) ?>
    <?= $this->Form->control('person_previous', ['type' => 'text']) ?>
    <?= $this->Form->control('birth_place_id', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<?php
echo $this->element('Admin/popup_form', [
    'popupId' => 'add-person-modal',
    'title' => 'Add New Person',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxAdd']),
    'targetSelectId' => 'person-id-select',
    'fields' => $personFields,
    'hiddenFormId' => 'hidden-person-form',
    'extraHtml' => '<div class="mb-3"><label class="form-label">Birth Place</label><input type="text" id="add-person-modal-birth-place-search" class="form-control" placeholder="Search places..." autocomplete="off"><div id="add-person-modal-birth-place-results" class="mt-1"></div><div id="add-person-modal-birth-place-selected" class="small mt-1"><span class="text-muted fst-italic">None selected</span></div></div>',
]);
?>

<?php $this->append('script'); ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const select = document.getElementById('person-id-select');
    if(!select) return;
    const currentValue = '<?= isset($teamSeasonRoster->person_id) ? (int)$teamSeasonRoster->person_id : '' ?>';
    const currentText = '<?= isset($teamSeasonRoster->person) ? h($teamSeasonRoster->person->display) : '' ?>';
    if (currentValue && currentText) {
        const opt = document.createElement('option');
        opt.value = currentValue; opt.textContent = currentText; select.appendChild(opt); select.value = currentValue;
    }
    const wrapper = document.createElement('div');
    wrapper.className = 'dynamic-person-wrapper';
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'form-control mb-1';
    searchInput.placeholder = 'Search persons...';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(searchInput);
    wrapper.appendChild(select);
    let debounceTimer = null; let lastQuery='';
    function performSearch(q){
        if (q.length < 2){ return; }
        fetch('<?= $this->Url->build(['prefix' => 'Admin','controller' => 'Persons','action' => 'ajaxSearch']) ?>?q=' + encodeURIComponent(q), {credentials:'same-origin'})
            .then(r=>r.json())
            .then(data => {
                if(!data.success) return;
                const current = select.value;
                const keepCurrent = current && !data.results.some(r => String(r.value) === String(current));
                const preserved = keepCurrent ? Array.from(select.options).find(o => o.value === current) : null;
                select.innerHTML='';
                const emptyOpt = document.createElement('option');
                emptyOpt.value=''; emptyOpt.textContent='(Select a person)'; select.appendChild(emptyOpt);
                if (preserved) select.appendChild(preserved);
                data.results.forEach(r => { const opt = document.createElement('option'); opt.value=r.value; opt.textContent=r.text; select.appendChild(opt); });
                if (current) select.value=current;
            })
            .catch(err => console.error('Person search failed', err));
    }
    searchInput.addEventListener('input', function(){
        const q = this.value.trim(); if (q===lastQuery) return; lastQuery=q; clearTimeout(debounceTimer); debounceTimer=setTimeout(()=>performSearch(q),300);
    });

    // Birth place AJAX lookup in person popup
    (function initBirthPlaceLookup() {
        const bpSearch = document.getElementById('add-person-modal-birth-place-search');
        const bpResults = document.getElementById('add-person-modal-birth-place-results');
        const bpSelected = document.getElementById('add-person-modal-birth-place-selected');
        const bpHidden = document.getElementById('add-person-modal-birth_place_id');
        if (!bpSearch || !bpHidden) return;

        const placeSearchUrl = '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxSearch']) ?>';
        let bpDebounce = null;

        function setBpSelected(id, text) {
            bpHidden.value = id;
            if (bpSelected) {
                bpSelected.innerHTML = '<span class="badge bg-primary me-1">' + text +
                    ' <button type="button" class="btn-close btn-close-white ms-1" aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>';
                bpSelected.querySelector('.btn-close').addEventListener('click', function() {
                    bpHidden.value = '';
                    bpSelected.innerHTML = '<span class="text-muted fst-italic">None selected</span>';
                });
            }
            if (bpResults) bpResults.innerHTML = '';
            bpSearch.value = '';
        }

        bpSearch.addEventListener('input', function() {
            clearTimeout(bpDebounce);
            const q = this.value.trim();
            if (q.length < 2) { if (bpResults) bpResults.innerHTML = ''; return; }
            bpDebounce = setTimeout(function() {
                fetch(placeSearchUrl + '?q=' + encodeURIComponent(q), {headers:{'X-Requested-With':'XMLHttpRequest'}})
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data.success || !data.results || !data.results.length) {
                            bpResults.innerHTML = '<div class="text-muted small">No results</div>';
                            return;
                        }
                        let html = '<div class="list-group list-group-flush" style="position:relative;z-index:1050;max-height:200px;overflow-y:auto;box-shadow:0 2px 8px rgba(0,0,0,.15)">';
                        data.results.forEach(function(r) {
                            const label = r.place_name + (r.place_state ? ', ' + r.place_state : '');
                            html += '<button type="button" class="list-group-item list-group-item-action py-1 small" data-id="' + r.id + '" data-text="' + label.replace(/"/g,'&quot;') + '">' + label + '</button>';
                        });
                        html += '</div>';
                        bpResults.innerHTML = html;
                        bpResults.querySelectorAll('button').forEach(function(btn) {
                            btn.addEventListener('click', function() { setBpSelected(btn.dataset.id, btn.dataset.text); });
                        });
                    })
                    .catch(function() { bpResults.innerHTML = '<div class="text-danger small">Error</div>'; });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!bpSearch.contains(e.target) && !bpResults.contains(e.target)) {
                bpResults.innerHTML = '';
            }
        });
    })();
});
</script>
<?php $this->end(); ?>
