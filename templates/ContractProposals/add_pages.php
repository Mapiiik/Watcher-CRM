<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var array<string, string> $documentTypes
 * @var array<string, string> $variants
 */

use App\Model\Enum\DocumentVariant;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Proposal Documents'),
                ['action' => 'documents', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals form content">
            <?= $this->element('ContractProposals/heading', ['doing' => __('Add Received Document')]) ?>

            <?php $this->Upload->load() ?>
            <?=
                $this->Form->create(null, [
                    'type' => 'file',
                    'url' => ['action' => 'addPages', $contractProposal->id],
                ] + $this->Upload->atMost())
                ?>
            <fieldset>
                <p><?=
                    __(
                        'What came back, as it came back. Several pages of one document go in'
                        . ' together and are filed in the order they are picked, which for a set of'
                        . ' scans is usually their own numbering.',
                    )
                    ?></p>
                <br>
                <?=
                    $this->Form->control('document_type', [
                        'label' => __('Document Type'),
                        'options' => $documentTypes,
                        'required' => true,
                    ])
                    ?>
                <?=
                    $this->Form->control('variant', [
                        'label' => __('Variant'),
                        'options' => $variants,
                        'default' => DocumentVariant::ReceivedSignedByCustomer->value,
                        'required' => true,
                    ])
                    ?>
                <?=
                    $this->Form->control('papers', [
                        'label' => __('Pages'),
                        'type' => 'file',
                        'multiple' => true,
                        'name' => 'papers[]',
                        'required' => true,
                    ])
                    ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
