<?php

/**
 * @var \App\View\AppView $this
 */
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="jumbotron">
                <h1 class="display-4">Welcome to Racer History</h1>
                <p class="lead">Track and manage historical sports information and statistics.</p>
                <hr class="my-4">
                <p>Get started by registering an account or logging in.</p>
                <?= $this->Html->link('Register', ['controller' => 'Users', 'action' => 'register'], ['class' => 'btn btn-primary btn-lg']) ?>
                <?= $this->Html->link('Login', ['controller' => 'Users', 'action' => 'login'], ['class' => 'btn btn-secondary btn-lg']) ?>
            </div>
        </div>
    </div>
</div>
