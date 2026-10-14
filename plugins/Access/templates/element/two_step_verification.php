<?php
/**
 * The two-step verification of an account: its methods, and what can be done with them. Its owner
 * sets it up or turns it off in the profile, an admin resets it on the account's page.
 *
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 * @var int $recoveryCodesLeft
 * @var bool $own Whether the account is the one signed in.
 */

use Access\Authentication\SecurityPolicy;

$required = SecurityPolicy::requiresOneTimePassword($user->toArray());
$totp = (bool)$user->secret_verified;
?>
<div class="related">
    <?php if ($own && !$totp) : ?>
        <?= $this->Html->link(
            __d('access', 'Set Up'),
            ['action' => 'oneTimePassword'],
            ['class' => 'button button-small float-right'],
        ) ?>
    <?php elseif ($own) : ?>
        <?php if (!$required) : ?>
            <?= $this->Html->link(
                __d('access', 'Turn Off'),
                ['action' => 'disableOneTimePassword'],
                ['class' => 'button button-small button-outline float-right'],
            ) ?>
        <?php endif; ?>
        <?= $this->Html->link(
            __d('access', 'New Recovery Codes'),
            ['action' => 'newRecoveryCodes'],
            ['class' => 'button button-small float-right'],
        ) ?>
    <?php elseif ($user->hasTwoStepVerification()) : ?>
        <?= $this->Form->postLink(
            __d('access', 'Reset'),
            ['action' => 'resetOneTimePasswordAuthenticator', $user->id],
            [
                'confirm' => __d(
                    'access',
                    'The account will have to set its authenticator application up again. Reset?',
                ),
                'class' => 'button button-small float-right',
            ],
        ) ?>
    <?php endif; ?>
    <h4><?= __d('access', 'Two-Step Verification') ?></h4>
    <table>
        <tr>
            <th><?= __d('access', 'Method') ?></th>
            <th><?= __d('access', 'State') ?></th>
            <th><?= __d('access', 'Recovery Codes Left') ?></th>
        </tr>
        <tr>
            <td><?= __d('access', 'Authenticator Application (TOTP)') ?></td>
            <td><?= $totp ? __d('access', 'On') : __d('access', 'Off') ?></td>
            <td><?= $totp ? $recoveryCodesLeft : '' ?></td>
        </tr>
    </table>
    <?php if (!$user->hasTwoStepVerification()) : ?>
        <?php if ($own) : ?>
            <p>
                <?= $required
                    ? __d('access', 'Your account has to use it, so you set it up the next time you sign in.')
                    : __d(
                        'access',
                        'Once it is on, signing in also asks for a verification code from an authenticator '
                        . 'application on your phone.',
                    ) ?>
            </p>
        <?php elseif ($required) : ?>
            <p>
                <?= __d(
                    'access',
                    'The account has to use it, so it is set up the next time the account signs in.',
                ) ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>
