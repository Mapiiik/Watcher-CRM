<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var iterable<\App\Model\Entity\ContractProposal> $proposals
 * @var array<string, array<string, array<string, array<\Files\Model\Entity\FileLink>>>> $filed
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Contract'),
                ['action' => 'view', $contract->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Edit Contract'),
                ['action' => 'edit', $contract->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $contract->id],
                ['class' => 'side-nav-item', 'target' => 'print'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Documentations'),
                ['controller' => 'Documentations', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('Customer Documents'),
                ['controller' => 'Customers', 'action' => 'documents', $contract->customer_id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts view content">
            <?= $this->element('Contracts/heading', ['doing' => __('Documents')]) ?>
            <?= $this->element('Contracts/facts', ['showMap' => false]) ?>
            <div class="related">
                <h4><?= __('Received Documents') ?></h4>
                <p><?=
                    __(
                        'The papers that came back, whoever signed them. They are filed against the'
                        . ' proposal they answer, so the row says which one that is.',
                    )
                    ?></p>
                <?php $this->Preview->load() ?>
                <?= $this->cell(
                    'Documents',
                    ['contract', $contract->id],
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
                    ['contract', $contract->id],
                    ['generatedByUs' => true],
                ) ?>
            </div>
        </div>
    </div>
</div>
