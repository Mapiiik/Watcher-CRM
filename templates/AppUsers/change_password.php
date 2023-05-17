<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppUser $user
 * @var bool $validatePassword
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_users', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_users', 'List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __d('app_users', 'User Profile'),
                ['action' => 'profile'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users form content">
            <?= $this->Flash->render('auth') ?>
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __d('app_users', 'Please enter the new password') ?></legend>
                <?php if ($validatePassword) : ?>
                    <?= $this->Form->control('current_password', [
                        'type' => 'password',
                        'required' => true,
                        'label' => __d('app_users', 'Current password')]);
                    ?>
                <?php endif; ?>
                <?= $this->Form->control('password', [
                    'type' => 'password',
                    'required' => true,
                    'label' => __d('app_users', 'New password'),
                ]); ?>
                <?= $this->Form->control('password_confirm', [
                    'type' => 'password',
                    'required' => true,
                    'label' => __d('app_users', 'Confirm password'),
                ]); ?>

            </fieldset>
            <?= $this->Form->button(__d('app_users', 'Submit')); ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
