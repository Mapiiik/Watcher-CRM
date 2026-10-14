<?php
/**
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 * @var list<string> $codes
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('access', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('access', 'User Profile'),
                ['action' => 'profile'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <?= $this->record(__d('access', 'Recovery Codes'), $user->name) ?>
            <p>
                <?= __d(
                    'access',
                    'Each of these codes signs you in once in place of a verification code, should you lose '
                    . 'your phone. Keep them somewhere safe, away from the phone. They are shown only now.',
                ) ?>
            </p>
            <div class="row">
                <div class="column column-50">
                    <table>
                        <?php foreach (array_chunk($codes, 2) as $pair) : ?>
                            <tr>
                                <?php foreach ($pair as $code) : ?>
                                    <td><code><?= h($code) ?></code></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <p><?= __d('access', 'New codes can be made in your profile, which makes these ones useless.') ?></p>
            <?= $this->Html->link(__d('access', 'Continue'), '/', ['class' => 'button']) ?>
        </div>
    </div>
</div>
