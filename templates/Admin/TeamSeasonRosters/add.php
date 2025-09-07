<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TeamSeasonRoster $teamSeasonRoster
 * @var \Cake\Collection\CollectionInterface|string[] $teamSeasonsList
 * @var \Cake\Collection\CollectionInterface|string[] $persons
 */
$this->assign('title', 'Add Team Season Roster');
?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <?php
            $teamSeasonId = $this->request->getQuery('team_season_id');
            if ($teamSeasonId) {
                $backUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            } else {
                $backUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
            }
            ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']) ?>">Team
                            Seasons</a></li>
                    <?php if ($teamSeasonId) : ?>
                    <li class="breadcrumb-item"><a href="<?= $backUrl ?>">Team Season View</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page">Add Roster</li>
                </ol>
            </nav>
            <h1 class="mb-3">Add Team Season Roster</h1>
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
                        // Placeholder select, options will be populated dynamically via AJAX search
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
                        echo $this->Form->control('roster_number', ['class' => 'form-control', 'label' => 'Number']);
                        echo $this->Form->control('roster_position', ['class' => 'form-control', 'label' => 'Position']);
                        echo $this->Form->control('roster_height', ['class' => 'form-control', 'label' => 'Height']);
                        echo $this->Form->control('roster_weight', ['class' => 'form-control', 'label' => 'Weight']);
                        ?>
                    </fieldset>
                    <div class="mt-3">
                        <?= $this->Form->button(__('Save Roster Entry'), ['class' => 'btn btn-primary']) ?>
                        <a href="<?= $backUrl ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$personFields = [
    ['name' => 'first_name', 'label' => 'First Name', 'required' => true, 'type' => 'text'],
    ['name' => 'last_name', 'label' => 'Last Name', 'required' => true, 'type' => 'text'],
    ['name' => 'birthdate', 'label' => 'Birthdate', 'type' => 'date'],
    ['name' => 'hometown', 'label' => 'Hometown', 'type' => 'text'],
    ['name' => 'homestate', 'label' => 'Homestate', 'type' => 'text'],
    ['name' => 'homecountry', 'label' => 'Home Country', 'type' => 'text'],
    ['name' => 'sport_id', 'label' => 'Primary Sport', 'type' => 'select', 'options' => $sports, 'required' => true],
];

echo $this->element('Admin/popup_form', [
    'popupId' => 'add-person-modal',
    'title' => 'Add New Person',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxAdd']),
    'targetSelectId' => 'person-id-select',
    'fields' => $personFields,
    'hiddenFormId' => 'main-roster-form',
]);
?>

<?php $this->append('script'); ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const select = document.getElementById('person-id-select');
    if(!select) return;
    // Convert select into an input+select hybrid for search: add a text input above it.
    const wrapper = document.createElement('div');
    wrapper.className = 'dynamic-person-wrapper';
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'form-control mb-1';
    searchInput.placeholder = 'Search persons...';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(searchInput);
    wrapper.appendChild(select);

    let debounceTimer = null;
    let lastQuery = '';
    function performSearch(q){
        if (q.length < 2){ return; }
        fetch('<?= $this->Url->build(['prefix' => 'Admin','controller' => 'Persons','action' => 'ajaxSearch']) ?>?q=' + encodeURIComponent(q), {credentials:'same-origin'})
            .then(r=>r.json())
            .then(data => {
                if(!data.success) return;
                const current = select.value;
                // Clear existing except maybe keep current if not in result set
                const keepCurrent = current && !data.results.some(r => String(r.value) === String(current));
                const preserved = keepCurrent ? Array.from(select.options).find(o => o.value === current) : null;
                select.innerHTML = '';
                const emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = '(Select a person)';
                select.appendChild(emptyOpt);
                if (preserved){ select.appendChild(preserved); }
                data.results.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.value; opt.textContent = r.text; select.appendChild(opt);
                });
                // Reselect previous value if still present
                if (current){ select.value = current; }
            })
            .catch(err => { console.error('Person search failed', err); });
    }
    searchInput.addEventListener('input', function(){
        const q = this.value.trim();
        if (q === lastQuery) return;
        lastQuery = q;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => performSearch(q), 300);
    });
});
</script>
<?php $this->end(); ?>
