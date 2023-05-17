<?php
/**
 * @var \App\View\AppView $this
 */

?>
<div class="users form content">
    <?= $this->Flash->render() ?>
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __d('app_users', 'Please enter your email') ?></legend>
        <?= $this->Form->control('email', ['type' => 'email', 'required' => true]) ?>
    </fieldset>
    <?= $this->Form->button(__d('app_users', 'Submit')); ?>
    <?= $this->Form->end() ?>
</div>
