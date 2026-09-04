<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Form\Form $printForm
 * @var \App\Model\Enum\ContractPrintType|null $printType
 * @var \App\Model\Entity\Contract $contract
 * @var iterable<\App\Model\Entity\ContractVersionProposal> $proposals
 * @var \App\Model\Entity\ContractVersionProposal|null $proposal
 * @var array<string, string> $documentTypes
 */

$howAProposalReads = function ($one) {
    $version = $one->contract_version ?? null;
    $period = $version === null
        ? ''
        : $version->valid_from . ' - ' . ($version->valid_until ?: __('indefinitely'));

    $state = match (true) {
        $one->hasBeenApplied() => __('carried over'),
        $one->hasBeenRevoked() => __('revoked'),
        $one->hasBeenConcluded() => __('concluded'),
        $one->hasBeenSent() => __('sent'),
        default => __('being drawn up'),
    };

    return sprintf(
        '%s — %s (%s), %s %s',
        $one->effective_from,
        $period,
        $state,
        __('snapshot taken'),
        $one->snapshot_taken,
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
                __('Draw Up a Proposal'),
                [
                    'controller' => 'ContractVersionProposals',
                    'action' => 'add',
                    '?' => ['contract_id' => $contract->id],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contracts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= __('Contract No.') ?><h3><?= h($contract->number) ?></h3>
            <h5><?= h($contract->service_type->name ?? '') ?></h5>

            <?= $this->Form->create($printForm, ['type' => 'get', 'valueSources' => ['query']]) ?>
            <fieldset>
                <legend><?= __('Print') ?></legend>
                <p><?= __(
                    'A document is printed from a proposal, so that the same paper printed twice is'
                    . ' the same paper. What it says is what the proposal took down, not what the'
                    . ' records happen to say today.',
                ) ?></p>
                <?php
                if ($options === []) {
                    echo '<p>' . __('There is no proposal on this contract yet.') . '</p>';
                } else {
                    echo $this->Form->control('proposal_id', [
                        'options' => $options,
                        'empty' => true,
                        'value' => $proposal?->id,
                        'label' => __('Proposal'),
                        'onchange' => 'this.form.submit()',
                    ]);

                    if ($proposal !== null) {
                        echo $this->Form->control('document_type', [
                            'options' => $documentTypes,
                            'empty' => true,
                            'value' => $printType?->value,
                            'label' => __('Type of Document'),
                        ]);
                        echo $this->Form->control('signed', [
                            'type' => 'checkbox',
                            'label' => __('Generate as signed'),
                        ]);
                    }
                }
                ?>
            </fieldset>
            <?php if ($proposal !== null) : ?>
                <?= $this->Form->button(__('Generate PDF'), [
                    'name' => 'submit_action',
                    'value' => 'pdf',
                ]) ?>
            <?php endif; ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
