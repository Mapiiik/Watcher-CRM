<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<string, string> $printed
 * @var array<string, string> $variants
 */

use App\Model\Enum\DocumentVariant;
use Cake\I18n\Date;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Proposal Documents'),
                ['action' => 'documents', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals form content">
            <?= $this->element('CustomerProposals/heading', ['doing' => __('Record the Signature')]) ?>

            <?php $this->Upload->load() ?>
            <?= $this->Form->create($customerProposal, ['type' => 'file'] + $this->Upload->atMost()) ?>
            <fieldset>
                <p><?= __('This is where the round ends. Nothing stands behind it waiting to be'
                    . ' written, so the day the customer agreed is the last thing it needs.') ?></p>
                <?= $this->Form->control('conclusion_date', [
                    'default' => Date::now(),
                    'label' => __('Conclusion Date'),
                    'help' => __('The day the customer agreed to it.'),
                ]) ?>
            </fieldset>
            <?php if ($printed !== []) : ?>
            <fieldset>
                <legend><?= __('What Came Back') ?></legend>
                <p><?=
                    __(
                        'Only the papers this round was printed as are offered, because nothing'
                        . ' else can have come back. Anything left empty is passed over, and the'
                        . ' day is recorded whether the scans are here or not.',
                    )
                    ?></p>
                <br>
                <?php foreach ($printed as $document_type => $label) : ?>
                <div class="row">
                    <div class="column column-50">
                        <?=
                            $this->Form->control('papers.' . $document_type, [
                                'label' => $label,
                                'type' => 'file',
                                'multiple' => true,
                                'name' => 'papers[' . $document_type . '][]',
                            ])
                        ?>
                    </div>
                    <div class="column column-50">
                        <?=
                            $this->Form->control('variants.' . $document_type, [
                                'label' => __('Variant'),
                                'options' => $variants,
                                'default' => DocumentVariant::ReceivedSignedByCustomer->value,
                            ])
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <p><?=
                    $this->AuthLink->link(
                        __('The rest of the papers'),
                        ['action' => 'documents', $customerProposal->id],
                    )
                    ?></p>
            </fieldset>
            <?php endif; ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
