<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<\App\Model\Entity\ContractProposal> $alsoInTheRound
 */

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
            <br>
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    '?' => ['proposal_id' => $customerProposal->id, 'agenda' => 'CustomerProposals'],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals form content">
            <?= $this->element('CustomerProposals/heading', ['doing' => __('Record the Signature')]) ?>

            <?= $this->Form->create($customerProposal) ?>
            <fieldset>
                <p><?= __('Enter the day the customer signed the proposal. If it holds contract'
                    . ' proposals, their changes are applied afterwards.') ?></p>
                <br>
                <?= $this->Form->control('conclusion_date', [
                    'default' => Date::now(),
                    'label' => __('Conclusion Date'),
                    'help' => __('The day the customer signed.'),
                ]) ?>
            </fieldset>
            <fieldset>
                <p><?=
                    $this->AuthLink->link(
                        __('Upload the signed documents on the documents page.'),
                        [
                            'plugin' => null,
                            'controller' => 'Documents',
                            'action' => 'manage',
                            '?' => [
                                'proposal_id' => $customerProposal->id,
                                'agenda' => 'CustomerProposals',
                            ],
                        ],
                    )
                    ?></p>
            </fieldset>
            <?=
                $this->element('common/also_in_the_round', [
                    'saying' => __('The same date is recorded for these as well, because they'
                        . ' came back together.'),
                ])
                ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
