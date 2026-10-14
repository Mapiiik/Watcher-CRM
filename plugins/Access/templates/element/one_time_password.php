<?php
/**
 * The two-step verification of the account signed in, for its profile.
 *
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 * @var int $recoveryCodesLeft
 */

use Access\Authentication\SecurityPolicy;

$required = SecurityPolicy::requiresOneTimePassword($user->toArray());

// a form addresses the plugin of its template unless told, and the controller is the application's
?>
<h4><?= __d('access', 'Two-Step Verification') ?></h4>
<h5><?= __d('access', 'Authenticator Application (TOTP)') ?></h5>
<?php if ($user->secret_verified) : ?>
    <p><?= __d('access', 'On. Recovery codes left: {0}.', $recoveryCodesLeft) ?></p>
    <div class="row">
        <div class="column">
            <?= $this->Form->create(null, ['url' => ['plugin' => null, 'action' => 'newRecoveryCodes']]) ?>
            <?= $this->Form->control('code', [
                'label' => __d('access', 'A verification code, to make new recovery codes'),
                'required' => true,
                'autocomplete' => 'one-time-code',
                'inputmode' => 'numeric',
            ]) ?>
            <?= $this->Form->button(__d('access', 'New Recovery Codes')) ?>
            <?= $this->Form->end() ?>
        </div>
        <div class="column">
            <?php if (!$required) : ?>
                <?= $this->Form->create(null, ['url' => ['plugin' => null, 'action' => 'disableOneTimePassword']]) ?>
                <?= $this->Form->control('code', [
                    'label' => __d('access', 'A verification code, to turn two-step verification off'),
                    'required' => true,
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                ]) ?>
                <?= $this->Form->button(__d('access', 'Turn Off'), ['class' => 'button-outline']) ?>
                <?= $this->Form->end() ?>
            <?php endif; ?>
        </div>
    </div>
<?php else : ?>
    <p>
        <?= $required
            ? __d('access', 'Off. Your account has to use it, so you set it up the next time you sign in.')
            : __d(
                'access',
                'Off. Once it is on, signing in also asks for a verification code from an authenticator '
                . 'application on your phone.',
            ) ?>
    </p>
    <?= $this->Html->link(__d('access', 'Set Up'), ['action' => 'oneTimePassword'], ['class' => 'button']) ?>
<?php endif; ?>
