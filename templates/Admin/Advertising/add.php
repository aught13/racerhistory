<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Advertisement $advertisement
 * @var array<string,string> $sports
 * @var array<int,array{label:string,sportKey:string}> $teams
 * @var array<string,string> $placementOptions
 */

$this->assign('title', 'Add Advertisement');
?>
<div class="container py-4">
    <?= $this->Flash->render() ?>
    <h1 class="mb-4">Add Advertisement</h1>
    <?= $this->element('Admin/Advertising/form', compact('advertisement', 'sports', 'teams', 'placementOptions')) ?>
</div>
