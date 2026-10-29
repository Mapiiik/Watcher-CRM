<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var \App\Contracts\Proposal\ProposedEquipment|null $line
 * @var array<string, mixed> $values
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $equipmentTypes
 */

$changing = $line === null
    ? __('Add a Borrowed Equipment')
    : __('Change a Borrowed Equipment');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if ($line !== null) : ?>
                <?= $this->AuthLink->postLink(
                    __('Take Back'),
                    ['action' => 'dropEquipmentLine', $contractProposal->id, $line->id],
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
        <div class="borrowedEquipments form content">
            <?= $this->element('ContractProposals/heading', ['doing' => $changing]) ?>

            <?= $this->Form->create(null) ?>
            <fieldset>
                <?php
                echo $this->Form->control('equipment_type_id', [
                    'options' => $equipmentTypes,
                    'empty' => true,
                    'value' => $values['equipment_type_id'] ?? null,
                    'label' => __('Equipment Type'),
                    'required' => true,
                ]);
                echo $this->Form->control('serial_number', [
                    'value' => $values['serial_number'] ?? null,
                    'label' => __('Serial Number'),
                ]);
                echo $this->Form->control('note', [
                    'value' => $values['note'] ?? null,
                    'label' => __('Note'),
                    'help' => __('The borrowed equipment is recorded on the contract when the changes'
                        . ' are applied, borrowed from the day it was handed over.'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
