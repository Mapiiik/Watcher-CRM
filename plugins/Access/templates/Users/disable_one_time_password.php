<?php
/**
 * @var \App\View\AppView $this
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
        <div class="users form content">
            <?= $this->Form->create() ?>
            <fieldset>
                <legend><?= __d('access', 'Turn Two-Step Verification Off') ?></legend>
                <?= $this->Form->control('code', [
                    'label' => __d('access', 'Verification Code (TOTP)'),
                    'required' => true,
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'autofocus' => true,
                    'help' => __d(
                        'access',
                        'Signing in then asks for the password alone, and the recovery codes stop working.',
                    ),
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__d('access', 'Turn Off')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
