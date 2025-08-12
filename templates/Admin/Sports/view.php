<?php $this->assign('title', 'View Sport'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Sport Details</h2>
                    <div class="btn-group" role="group">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                           class="btn btn-primary btn-sm">Edit</a>
                        <?= $this->Form->postLink('Delete',
                            ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id],
                            ['class' => 'btn btn-danger btn-sm', 'confirm' => 'Are you sure you want to delete this sport?']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th class="w-25">ID:</th>
                                <td><?= h($sport->id) ?></td>
                            </tr>
                            <tr>
                                <th>Sport Name:</th>
                                <td><?= h($sport->sport_name) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'index']) ?>"
                           class="btn btn-secondary">Back to Sports List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
