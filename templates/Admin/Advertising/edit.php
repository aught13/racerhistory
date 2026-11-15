<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Advertisement $advertisement
 * @var array<string,string> $sports
 * @var array<int,array{label:string,sportKey:string}> $teams
 * @var array<string,string> $placementOptions
 */

$this->assign('title', 'Edit Advertisement');
?>
<div class="container py-4">
    <?= $this->Flash->render() ?>
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="mb-0">Edit Advertisement</h1>
        <?= $this->Form->postButton(
            'Delete',
            ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'delete', (int)$advertisement->id],
            ['class' => 'btn btn-outline-danger', 'data-turbo-frame' => 'admin-content'],
        ) ?>
    </div>
    <?= $this->element('Admin/Advertising/form', compact('advertisement', 'sports', 'teams', 'placementOptions')) ?>
</div>
