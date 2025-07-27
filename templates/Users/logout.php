<!-- templates/Users/logout.php -->
<?php $this->assign('title', 'Logout'); ?>
<div class="users logout">
    <h1>Logout</h1>
    <p>You have been logged out.</p>
    <p><?= $this->Html->link('Login again', ['action' => 'login']) ?></p>
</div>
