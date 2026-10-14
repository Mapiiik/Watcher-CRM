<?php
/**
 * @var \App\View\AppView $this
 * @var list<string> $codes
 */
?>
<div class="row">
    <div class="column column-90">
        <div class="users view content">
            <h3><?= __d('access', 'Recovery Codes') ?></h3>
            <p><?= __d(
                'access',
                'Each of these codes signs you in once in place of a verification code, should you lose your phone. '
                . 'Keep them somewhere safe, away from the phone. They are shown only now.',
            ) ?></p>
            <ul class="recovery-codes">
                <?php foreach ($codes as $code) : ?>
                    <li><code><?= h($code) ?></code></li>
                <?php endforeach; ?>
            </ul>
            <p><?= __d('access', 'New codes can be made in your profile, which makes these ones useless.') ?></p>
            <?= $this->Html->link(__d('access', 'Continue'), '/', ['class' => 'button']) ?>
        </div>
    </div>
</div>
