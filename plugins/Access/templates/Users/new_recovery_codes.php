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
                <legend><?= __d('access', 'New Recovery Codes') ?></legend>
                <?= $this->Form->control('code', [
                    'label' => __d('access', 'Verification Code (TOTP)'),
                    'required' => true,
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'autofocus' => true,
                    'help' => __d(
                        'access',
                        'The recovery codes you have now stop working, and the new ones are shown once.',
                    ),
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__d('access', 'New Recovery Codes')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
