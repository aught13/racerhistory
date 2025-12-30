<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost[] $posts */
?>
<?php $this->assign('title', 'Blog Posts'); ?>
<div class="container py-4" aria-label="Blog Posts">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Blog Posts</h1>
        <?= $this->Html->link('Add Post', ['action' => 'add'], ['class' => 'btn btn-primary']) ?>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Tags</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No posts yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= h($post->title) ?></td>
                                <td>
                                    <?php if ($post->is_published): ?>
                                        <span class="badge bg-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if (!empty($post->published_at)): ?>
                                        <?= h($post->published_at->format('Y-m-d')) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($post->blog_tags)): ?>
                                        <?php foreach ($post->blog_tags as $tag): ?>
                                            <span class="badge bg-info text-dark me-1 mb-1"><?= h($tag->name) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?= $this->Html->link('Edit', ['action' => 'edit', $post->id], ['class' => 'btn btn-sm btn-outline-primary me-2']) ?>
                                    <?= $this->Form->postLink('Delete', ['action' => 'delete', $post->id], [
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'confirm' => 'Are you sure you want to delete this post?',
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
