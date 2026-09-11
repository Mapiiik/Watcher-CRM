<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Form\Form $printForm
 * @var \App\Model\Entity\CustomerProposal|null $proposal
 * @var array<string, string> $documentTypes
 * @var \App\Model\Enum\CustomerPrintType|null $printType
 * @var \App\Model\Entity\Customer $customer
 */

$howARoundReads = function ($one): string {
    return sprintf(
        '%s - %s (%s)',
        $one->effective_from,
        $one->purpose->label(),
        $one->getState(),
    );
};

$rounds = [];
foreach ($customer->customer_proposals ?? [] as $one) {
    $rounds[$one->id] = $howARoundReads($one);
}

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
                __('Customer Documents'),
                ['action' => 'documents', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= $this->element('Customers/heading') ?>
            <?= $this->element('Customers/facts') ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Proposal'),
                    [
                        'controller' => 'CustomerProposals',
                        'action' => 'add',
                        'customer_id' => $customer->id,
                    ],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Proposals') ?></h4>
                <?= $this->element('Customers/CustomerProposals', [
                    'customer_proposals' => $customer->customer_proposals ?? [],
                ]) ?>
            </div>
            <br>
            <?= $this->Form->create($printForm, [
                'type' => 'get',
                'valueSources' => ['query'],
                'url' => [
                    'action' => 'print',
                    $customer->id,
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Print Documents') ?></legend>
                <p><?= __('A document is printed from a round of papers, so that the same paper'
                    . ' printed twice is the same paper and a signed scan has something to be'
                    . ' filed against.') ?></p>
                <?php
                if ($rounds === []) {
                    echo '<p>' . __('There is no round of papers for this customer yet.') . '</p>';
                } else {
                    echo $this->Form->control('proposal_id', [
                        'label' => __('Proposal'),
                        'options' => $rounds,
                        'empty' => true,
                        'value' => $proposal?->id,
                        'required' => true,
                        'onchange' => $this::SUBMIT_ON_CHANGE,
                    ]);

                    if ($proposal !== null) {
                        echo $this->Form->control('document_type', [
                            'label' => __('Document Type'),
                            'options' => $documentTypes,
                            'empty' => true,
                            'value' => $printType?->value,
                            'required' => true,
                            'onchange' => $this::SUBMIT_ON_CHANGE,
                        ]);
                    }
                }
                ?>
            </fieldset>
            <?= $this->Form->hidden('submit_action', [
                'value' => 'refresh',
            ]) ?>
            <?php if ($proposal !== null && $printType !== null) : ?>
                <?= $this->Form->button(__('Print to PDF'), [
                    'name' => 'submit_action',
                    'value' => 'pdf',
                ]) ?>
            <?php endif; ?>
            <?= $this->Form->end() ?>

            <div class="related">
                <h4><?= __('Documents Already Generated') ?></h4>
                <p><?=
                    __(
                        'What this form has printed before. A document is generated once, so these'
                        . ' are the ones the customer was given - fetching one back is quicker than'
                        . ' printing it again, and it is the same file either way.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['customer', $customer->id],
                    ['generatedByUs' => true, 'withContracts' => false],
                ) ?>
            </div>
        </div>
    </div>
</div>
