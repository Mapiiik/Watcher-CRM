<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="users form content">
    <?= $this->Flash->render('auth') ?>
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __d('access', 'Please enter your username and password') ?></legend>
        <?= $this->Form->control('username', ['label' => __d('access', 'Username'), 'required' => true]) ?>
        <?= $this->Form->control('password', ['label' => __d('access', 'Password'), 'required' => true]) ?>
        <?= $this->Html->link(__d('access', 'Reset Password'), ['action' => 'requestResetPassword']) ?>
    </fieldset>
    <?= $this->Form->button(__d('access', 'Login')); ?>
    <?= $this->element('Access.social_sign_in') ?>
    <?= $this->Form->end() ?>
</div>
