<?php
/**
 * Bulk message wizard — step 1: choose the message purpose.
 *
 * @var \App\View\AppView $this
 * @var array<int, string> $purposes
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Customer Messages'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerMessages form content">
            <?= $this->Form->create(null) ?>
            <fieldset>
                <legend><?= __('Bulk Customer Message') . ' — ' . __(
                    'Step {0} of {1}: {2}',
                    1,
                    3,
                    __('Purpose'),
                ) ?></legend>
                <?= $this->Form->control('purpose', [
                    'type' => 'radio',
                    'options' => $purposes,
                    'label' => __(
                        'Choose the purpose of the message. It determines the available filters '
                        . 'and which mailing consent is required.',
                    ),
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__('Continue')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
