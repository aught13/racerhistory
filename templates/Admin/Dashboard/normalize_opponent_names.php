<?php
/**
 * @var \App\View\AppView $this
 * @var array{total: int, rows: list<array{id: int, original: string, normalized: string}>} $preview
 */

$this->assign('title', 'Normalize Opponent Player Names');
?>
<div class="admin dashboard">
    <h1 class="h3 mb-3">Normalize Basketball Opponent Player Names</h1>
    <p>
        This one-time cleanup changes comma-separated names in basketball game opponent player stats
        to <em>First Last</em> format and removes a standalone or trailing Jr. suffix.
        Rows that cannot be parsed as a two-part name are left unchanged.
    </p>

    <div class="alert alert-warning" role="alert">
        Review the preview before running this database update. The operation changes existing records
        and cannot be undone from this page.
    </div>

    <p><strong>Records that would change:</strong> <?= $preview['total'] ?></p>

    <?php if ($preview['rows'] !== []) : ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Current name</th>
                        <th scope="col">Normalized name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview['rows'] as $row) : ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= h($row['original']) ?></td>
                            <td><?= h($row['normalized']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($preview['total'] > count($preview['rows'])) : ?>
            <p>Showing the first <?= count($preview['rows']) ?> records only.</p>
        <?php endif; ?>
    <?php else : ?>
        <p>No records need normalization.</p>
    <?php endif; ?>

    <?= $this->Form->create(null, [
        'url' => ['controller' => 'Dashboard', 'action' => 'normalizeOpponentNames', 'prefix' => 'Admin'],
        'id' => 'normalize-opponent-names-form',
    ]) ?>
        <div class="mb-3">
            <?= $this->Form->control('confirmation', [
                'label' => 'Enter NORMALIZE to confirm',
                'required' => true,
                'autocomplete' => 'off',
                'pattern' => 'NORMALIZE',
                'class' => 'form-control',
            ]) ?>
        </div>
        <button type="submit" class="btn btn-danger" <?= $preview['total'] === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-exclamation-triangle me-2"></i>Normalize names
        </button>
        <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Admin']) ?>"
           class="btn btn-secondary"
           data-turbo-frame="admin-content">Cancel</a>
    <?= $this->Form->end() ?>
</div>