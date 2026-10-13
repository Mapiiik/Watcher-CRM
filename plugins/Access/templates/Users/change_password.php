<?php
/**
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 * @var bool $validatePassword
 * @var int $passwordMinLength
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('access', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('access', 'List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __d('access', 'User Profile'),
                ['action' => 'profile'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users form content">
            <?= $this->Flash->render('auth') ?>
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __d('access', 'Please enter the new password') ?></legend>
                <?php if ($validatePassword) : ?>
                    <?= $this->Form->control('current_password', [
                        'type' => 'password',
                        'value' => '',
                        'required' => true,
                        'autocomplete' => 'current-password',
                        'label' => __d('access', 'Current password'),
                    ]) ?>
                <?php endif; ?>
                <?php // the account comes with its hash, which is no value for a field to show ?>
                <?= $this->Form->control('password', [
                    'type' => 'password',
                    'value' => '',
                    'id' => 'new-password',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'label' => __d('access', 'New password'),
                    'help' => __d(
                        'access',
                        'At least {0} characters, not the username or the e-mail and not a common password.',
                        $passwordMinLength,
                    ),
                ]); ?>
                <?= $this->Form->control('password_confirm', [
                    'type' => 'password',
                    'value' => '',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'label' => __d('access', 'Confirm password'),
                ]); ?>

            </fieldset>
            <?= $this->Form->button(__d('access', 'Submit'), ['id' => 'btn-submit']); ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
