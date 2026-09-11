<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var iterable<\App\Model\Entity\ContractProposal> $proposals
 * @var array<string, array<string, array<string, array<\Files\Model\Entity\FileLink>>>> $filed
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Customer'),
                ['action' => 'view', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Edit Customer'),
                ['action' => 'edit', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $customer->id],
                ['class' => 'side-nav-item', 'target' => 'print'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customers view content">
            <?= $this->element('Customers/heading') ?>
            <?= $this->element('Customers/facts') ?>
            <div class="related">
                <h4><?= __('Received Documents') ?></h4>
                <p><?=
                    __(
                        'The papers that came back, across every contract this customer has. They'
                        . ' are filed against the proposal they answer, so the row says which one'
                        . ' that is.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['customer', $customer->id],
                    ['generatedByUs' => false],
                ) ?>
            </div>
            <div class="related">
                <h4><?= __('Generated Documents') ?></h4>
                <p><?=
                    __(
                        'What we generated. A document is generated once and handed back'
                        . ' afterwards, so these are the very files the customer was given.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['customer', $customer->id],
                    ['generatedByUs' => true],
                ) ?>
            </div>
        </div>
    </div>
</div>
