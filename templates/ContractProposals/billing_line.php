<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var \App\Contracts\Proposal\ProposedBilling|null $line
 * @var \App\Model\Entity\Billing|null $replaced
 * @var array<string, mixed> $values
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $services
 * @var bool $below_minimum_override
 * @var string|null $below_minimum_refused Why the price was refused, where it was.
 */

$changing = $replaced === null
    ? __('Add to What Is Billed For')
    : __('Change What Is Billed For');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if ($line !== null) : ?>
                <?= $this->AuthLink->postLink(
                    __('Take Back'),
                    ['action' => 'dropBillingLine', $contractProposal->id, $line->id],
                    [
                        'confirm' => __('Leave this as it stands on the contract?'),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?php endif; ?>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="billings form content">
            <?= $this->element('ContractProposals/heading', ['doing' => $changing]) ?>

            <?= $this->Form->create(null) ?>
            <fieldset>

                <?php if ($replaced !== null) : ?>
                <div class="message" role="status">
                    <?= __(
                        'Replacing {0}, billed from {1}. It stops the day before this one starts.',
                        h($replaced->name),
                        h($replaced->billing_from),
                    ) ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="column">
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
                        ?>
                    </div>
                    <div class="column">
                        <?php
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
                        // The form has no record to hang the refusal on, so it is said under the
                        // price it is about, the way the form would say it.
                        if (!empty($below_minimum_refused)) {
                            echo $this->Html->div('error-message', h($below_minimum_refused));
                        }
                        echo $this->Form->control('fixed_discount', [
                            'value' => $values['fixed_discount'] ?? null,
                            'label' => __('Fixed Discount'),
                        ]);
                        echo $this->Form->control('percentage_discount', [
                            'type' => 'number',
                            'value' => $values['percentage_discount'] ?? null,
                            'label' => __('Percentage Discount'),
                        ]);
                        ?>
                    </div>
                </div>
                <?php
                echo $this->Form->control('billing_from', [
                    'type' => 'date',
                    'empty' => true,
                    'value' => $values['billing_from'] ?? null,
                    'label' => __('Billing From'),
                    'help' => __('Empty starts with the contract proposal.'),
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
                if (!empty($below_minimum_override)) {
                    echo $this->Form->control('below_minimum_allowed', [
                        'type' => 'checkbox',
                        'checked' => (bool)($values['below_minimum_allowed'] ?? false),
                        'label' => __('Allow a connection price below the minimum set on the contract'),
                    ]);
                }
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
