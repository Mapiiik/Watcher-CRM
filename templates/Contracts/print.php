<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Form\Form $printForm
 * @var \App\Model\Enum\ContractPrintType|null $printType
 * @var \App\Model\Entity\Contract $contract
 * @var iterable<\App\Model\Entity\ContractProposal> $proposals
 * @var \App\Model\Entity\ContractProposal|null $proposal
 * @var array<string, string> $documentTypes
 */

$howAProposalReads = function ($one): string {
    $version = $one->contract_version ?? null;
    $period = $version === null
        ? ''
        : $version->name;

    return sprintf(
        '%s - %s - %s (%s)',
        $one->effective_from,
        $one->purpose->label(),
        $period,
        $one->getState(),
    );
};

$options = [];
foreach ($proposals as $one) {
    $options[$one->id] = $howAProposalReads($one);
}

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
                __('Contract Documents'),
                ['action' => 'documents', $contract->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Customer Documents'),
                ['controller' => 'Customers', 'action' => 'documents', $contract->customer_id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= $this->element('Contracts/heading') ?>
            <?= $this->element('Contracts/facts', ['showMap' => false]) ?>
            <?php if ($contract->service_type !== null && $contract->service_type->have_contract_versions) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract Version'),
                    ['controller' => 'ContractVersions', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Contract Versions') ?></h4>
                <?= $this->element('Contracts/ContractVersions', [
                    'contract_versions' => $contract->contract_versions,
                ]) ?>
            </div>
            <?php endif; ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Proposal'),
                    [
                        'controller' => 'ContractProposals',
                        'action' => 'add',
                        'customer_id' => $contract->customer_id,
                        'contract_id' => $contract->id,
                    ],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Proposals') ?></h4>
                <?= $this->element('Contracts/ContractProposals', [
                    'contract_proposals' => $proposals,
                ]) ?>
            </div>
            <br>
            <?= $this->Form->create($printForm, [
                'type' => 'get',
                'valueSources' => ['query'],
                'url' => [
                    'action' => 'print',
                    $contract->id,
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Print Documents') ?></legend>
                <p><?= __('A document is printed from a proposal, so that the same paper printed'
                    . ' twice is the same paper. What it says is what the proposal took down, not'
                    . ' what the records happen to say today.') ?></p>
                <?php
                if ($options === []) {
                    echo '<p>' . __('There is no proposal on this contract yet.') . '</p>';
                } else {
                    echo $this->Form->control('proposal_id', [
                        'label' => __('Proposal'),
                        'options' => $options,
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
                        if ($printType?->mayCarryOurSignature()) {
                            echo $this->Form->control('signed', [
                                'label' => __('Signed'),
                                'type' => 'checkbox',
                            ]);
                        }
                    }
                }
                ?>
            </fieldset>
            <?php if ($proposal !== null && $printType !== null) : ?>
                <?= $this->Form->button(__('Print to PDF'), [
                    'name' => 'submit_action',
                    'value' => 'pdf',
                ]) ?>
            <?php endif; ?>
            <?= $this->Form->end() ?>

            <div class="related">
                <h4><?= __('Papers Already Drawn Up') ?></h4>
                <p><?=
                    __(
                        'What has been printed from this contract before. A paper is drawn once,'
                        . ' so these are the ones the customer was given - fetching one back is'
                        . ' quicker than printing it again, and it is the same file either way.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['contract', $contract->id],
                    ['ours' => true],
                ) ?>
            </div>
        </div>
    </div>
</div>
