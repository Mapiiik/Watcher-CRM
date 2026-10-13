<?php
/**
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 */

?>
<div class="users form content">
    <?= $this->Flash->render('auth') ?>
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __d('access', 'Please enter your email or username to reset your password') ?></legend>
        <?= $this->Form->control('reference') ?>
    </fieldset>
    <?= $this->Form->button(__d('access', 'Submit')); ?>
    <?= $this->Form->end() ?>
</div>
