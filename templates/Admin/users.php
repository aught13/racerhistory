<h1>Manage Users</h1>
<?php if (isset($users)): ?>
<ul>
	<?php foreach ($users as $u): ?>
	<li><?= h($u->username) ?> (<?= h($u->status) ?>)</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>
