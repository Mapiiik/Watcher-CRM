<?php
/**
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 * @var int $passwordMinLength
 */
?>
<div class="users form content">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __d('access', 'A new password for {0}', h($user->username)) ?></legend>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'value' => '',
            'required' => true,
            'autocomplete' => 'new-password',
            'label' => __d('access', 'New password'),
            'help' => __d(
                'access',
                'At least {0} characters, not the username or the e-mail and not a common password.',
                $passwordMinLength,
            ),
        ]) ?>
        <?= $this->Form->control('password_confirm', [
            'type' => 'password',
            'value' => '',
            'required' => true,
            'autocomplete' => 'new-password',
            'label' => __d('access', 'Confirm password'),
        ]) ?>
    </fieldset>
    <?= $this->Form->button(__d('access', 'Set the password')) ?>
    <?= $this->Form->end() ?>
</div>
