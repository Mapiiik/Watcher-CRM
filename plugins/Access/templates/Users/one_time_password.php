<?php
/**
 * @var \App\View\AppView $this
 * @var string $secretDataUri
 * @var string $secret
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
                <legend><?= __d('access', 'Set Up One-Time Codes') ?></legend>
                <?= $this->element('Access.one_time_password_setup', compact('secretDataUri', 'secret')) ?>
                <?= $this->Form->control('code', [
                    'label' => __d('access', 'Code'),
                    'required' => true,
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'autofocus' => true,
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__d('access', 'Turn On')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
