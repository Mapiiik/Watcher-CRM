<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var \App\Contracts\Proposal\ProposedBilling|null $line
 * @var \App\Model\Entity\Billing|null $replaced
 * @var array<string, mixed> $values
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $services
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersionProposals form content">
            <?= $this->Form->create(null) ?>
            <fieldset>
                <legend><?= $replaced === null
                    ? __('Add to What Is Billed For')
                    : __('Change What Is Billed For') ?></legend>

                <?php if ($replaced !== null) : ?>
                    <p><?= __('Replacing: {0}', h($replaced->name)) ?>
                        <?= __('billed from {0}', h($replaced->billing_from)) ?></p>
                <?php endif; ?>

                <p><?= __(
                    'Leave the day it starts empty to start with the papers. Two lines make one'
                    . ' change into several - half price until a day, and full price from it.',
                ) ?></p>

                <?php
                echo $this->Form->control('service_id', [
                    'options' => $services,
                    'empty' => true,
                    'value' => $values['service_id'] ?? null,
                    'label' => __('Service'),
                ]);
                echo $this->Form->control('text', [
                    'value' => $values['text'] ?? null,
                    'label' => __('Text'),
                ]);
                echo $this->Form->control('quantity', [
                    'type' => 'number',
                    'value' => $values['quantity'] ?? 1,
                    'label' => __('Quantity'),
                ]);
                echo $this->Form->control('price', [
                    'value' => $values['price'] ?? null,
                    'label' => __('Price'),
                    'placeholder' => __('Price list'),
                ]);
                echo $this->Form->control('fixed_discount', [
                    'value' => $values['fixed_discount'] ?? null,
                    'label' => __('Fixed Discount'),
                ]);
                echo $this->Form->control('percentage_discount', [
                    'type' => 'number',
                    'value' => $values['percentage_discount'] ?? null,
                    'label' => __('Percentage Discount'),
                ]);
                echo $this->Form->control('billing_from', [
                    'type' => 'date',
                    'empty' => true,
                    'value' => $values['billing_from'] ?? null,
                    'label' => __('Billing From'),
                ]);
                echo $this->Form->control('billing_until', [
                    'type' => 'date',
                    'empty' => true,
                    'value' => $values['billing_until'] ?? null,
                    'label' => __('Billing Until'),
                ]);
                echo $this->Form->control('separate_invoice', [
                    'type' => 'checkbox',
                    'checked' => (bool)($values['separate_invoice'] ?? false),
                    'label' => __('Separate Invoice'),
                ]);
                echo $this->Form->control('note', [
                    'value' => $values['note'] ?? null,
                    'label' => __('Note'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
