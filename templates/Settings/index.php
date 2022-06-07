<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="settings index content">
    <h3><?= __('Settings') ?></h3>
    <div class="table-responsive">
        <?= $this->AuthLink->link(
            __('User Profile'),
            ['controller' => 'Users', 'action' => 'profile', 'plugin' => 'CakeDC/Users'],
            ['class' => 'side-nav-item']
        ) ?>
        <?= $this->AuthLink->link(
            __('List Users'),
            ['controller' => 'Users', 'action' => 'index', 'plugin' => 'CakeDC/Users'],
            ['class' => 'side-nav-item']
        ) ?>
    </div>
</div>
