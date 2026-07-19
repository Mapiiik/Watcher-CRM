<?php
/**
 * Bulk message wizard — step 2: choose recipient filters and consent handling.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Enum\CustomerMessagePurpose $purpose
 * @var list<array{name: string, options: array<string, mixed>}> $filterControls
 * @var bool $ignoreCustomerConsent
 * @var bool $ignoreContactUse
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Back'),
                ['action' => 'addBulkNew', '?' => ['step' => 'purpose']],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __('Start Over'),
                ['action' => 'addBulkNew', '?' => ['reset' => 1]],
                ['class' => 'side-nav-item'],
            ) ?>
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
                <legend><?= __('Bulk Customer Message') . ' — ' . __('Step {0} of {1}: {2}', 2, 3, __('Filters')) ?></legend>
                <p><?= __('Purpose: {0}', $purpose->label()) ?></p>
                <hr>
                <?php if ($filterControls === []) : ?>
                    <p><?= __('This purpose offers no filters.') ?></p>
                <?php endif; ?>
                <?php foreach ($filterControls as $control) : ?>
                    <?= $this->Form->control($control['name'], $control['options']) ?>
                <?php endforeach; ?>
            </fieldset>
            <hr>
            <fieldset>
                <legend><?= __('Consent overrides (exceptional cases only)') ?></legend>
                <?= $this->Form->control('ignore_customer_consent', [
                    'type' => 'checkbox',
                    'label' => __('Ignore customer mailing consent'),
                    'checked' => $ignoreCustomerConsent,
                ]) ?>
                <p><?= __(
                    'Include customers regardless of their mailing consent for this purpose '
                    . '(agree_mailing_*). Use only when legally justified.',
                ) ?></p>
                <br>
                <?= $this->Form->control('ignore_contact_use', [
                    'type' => 'checkbox',
                    'label' => __('Ignore per-contact routing flag'),
                    'checked' => $ignoreContactUse,
                ]) ?>
                <p><?= __(
                    'Send to every email/phone of the selected customers, regardless of the '
                    . 'per-contact routing flag (use_for_*).',
                ) ?></p>
            </fieldset>
            <hr>
            <?= $this->Form->button(__('Continue')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
