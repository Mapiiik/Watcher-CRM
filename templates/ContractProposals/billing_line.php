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
 * @var \Cake\I18n\Date|null $last_day_of_the_term The last day of a fixed term that is running.
 * @var string|null $past_the_term_refused Why the days were refused, where they were.
 */

$changing = $replaced === null
    ? __('Add a Billing')
    : __('Change a Billing');
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
                        'confirm' => __('Leave this as it is on the contract?'),
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
                        'Replaces {0}, billed from {1}, which ends the day before this item'
                        . ' starts.',
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
                        // No record stands behind this form, so the types are not read off a schema.
                        echo $this->Form->control('price', [
                            'type' => 'number',
                            'step' => '0.01',
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
                            'type' => 'number',
                            'step' => '0.01',
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
                $latest = $last_day_of_the_term?->toDateString();

                echo $this->Form->control('billing_from', [
                    'type' => 'date',
                    'empty' => true,
                    'max' => $latest,
                    'value' => $values['billing_from'] ?? null,
                    'label' => __('Billing From'),
                    'help' => __('If left empty, billing starts on the day the contract'
                        . ' proposal takes effect.'),
                ]);
                echo $this->Form->control('billing_until', [
                    'type' => 'date',
                    'empty' => true,
                    'max' => $latest,
                    'value' => $values['billing_until'] ?? null,
                    'label' => __('Billing Until'),
                ]);
                if (!empty($past_the_term_refused)) {
                    echo $this->Html->div('error-message', h($past_the_term_refused));
                }
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
