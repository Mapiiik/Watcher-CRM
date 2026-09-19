<?php
/**
 * Filing what came back, a document at a time.
 *
 * Opened from the workbench and returning to it, so the heading names the papers rather than the
 * page: what is being done is said once, over the form.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round
 * @var string $about What the papers are of.
 * @var array<string, array<string, mixed>> $printed
 * @var array<string, string> $variants
 */

use App\Model\Entity\CustomerProposal;
use App\Model\Enum\DocumentVariant;

$agenda = $round instanceof CustomerProposal ? 'CustomerProposals' : 'ContractProposals';
$there = ['proposal_id' => $round->id, 'agenda' => $agenda];

// Which paper of which record, in one field: the scan is of one document, and a proposal put to
// the customer holds their own papers beside those of each of their contracts.
$offered = [];

foreach ($printed as $holder => $whose) {
    foreach ($whose['documents'] as $document_type => $label) {
        $offered[$whose['said']][$holder . '/' . $document_type] = $label;
    }
}
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Documents'),
                ['action' => 'manage', '?' => $there],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['controller' => $agenda, 'action' => 'view', $round->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="documents form content">
            <?= $this->record(__('Documents'), $about, doing: __('Add Received Document')) ?>
            <br>

            <?php $this->Upload->load() ?>
            <?=
                $this->Form->create(null, [
                    'type' => 'file',
                    'url' => ['action' => 'addPages', '?' => $there],
                ] + $this->Upload->atMost())
                ?>
            <fieldset>
                <p><?=
                    __(
                        'Upload the documents that came back for this proposal. The pages of'
                        . ' one document are uploaded together and filed in the order you select'
                        . ' them.',
                    )
                    ?></p>
                <br>
                <?=
                    $this->Form->control('document_type', [
                        'label' => __('Document Type'),
                        'options' => $offered,
                        'empty' => true,
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
