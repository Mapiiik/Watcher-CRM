<?php
/**
 * @var \App\View\AppView $this
 * @var string|null $secretDataUri
 * @var string|null $secret
 */

$settingUp = !empty($secretDataUri) && !empty($secret);
?>
<div class="users form content">
    <?= $this->Flash->render('auth') ?>
    <?= $this->Flash->render() ?>
    <?= $this->Form->create() ?>
    <fieldset>
        <?php if ($settingUp) : ?>
            <legend><?= __d('access', 'Set Up Two-Step Verification') ?></legend>
            <p>
                <?= __d(
                    'access',
                    'Your account signs in with the password and a verification code from an authenticator '
                    . 'application on your phone.',
                ) ?>
            </p>
            <?= $this->element('Access.one_time_password_setup', compact('secretDataUri', 'secret')) ?>
        <?php else : ?>
            <legend><?= __d('access', 'Enter the verification code from your authenticator application') ?></legend>
        <?php endif; ?>
        <?= $this->Form->control('code', [
            'label' => __d('access', 'Verification Code'),
            'required' => true,
            'autocomplete' => 'one-time-code',
            'autofocus' => true,
            'help' => $settingUp ? null : __d('access', 'Lost your phone? Enter one of your recovery codes instead.'),
        ]) ?>
    </fieldset>
    <?= $this->Form->button(__d('access', 'Verify')) ?>
    <?= $this->Form->end() ?>
</div>
