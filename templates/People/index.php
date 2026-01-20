<?php
declare(strict_types=1);
/** @var array<int,\App\Model\Entity\Person> $people */
$this->assign('title', 'People');
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">People</h1>
        <p class="text-muted mb-0">Players, coaches, and staff</p>
    </div>

    <?php if (!empty($people)) : ?>
        <!-- Search/Filter -->
        <div class="mb-4">
            <input type="text" id="peopleSearch" class="form-control" placeholder="Search by name...">
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover" id="peopleTable">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($people as $person) : ?>
                        <tr>
                            <td>
                                <?= h($person->first_name) ?> <?= h($person->last_name) ?>
                                <?php if (!empty($person->nickname)) : ?>
                                    <small class="text-muted">"<?= h($person->nickname) ?>"</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $person->id]) ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Profile
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No people records available yet.
        </div>
    <?php endif; ?>
</div>

<script>
// Simple client-side search
document.getElementById('peopleSearch')?.addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#peopleTable tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>
