<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<\App\Model\Entity\ContractProposal> $alsoInTheRound
 * @var array<int|string, string> $deliveryTypes
 */

use Cake\I18n\Date;

// Papers do go out more than once - by another means, or after the first attempt came back.
// The day is what a second sending moves.
$again = $customerProposal->hasBeenSent();
$recording = $again ? __('Record the Sending Again') : __('Record the Sending');
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
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals form content">
            <?= $this->element('CustomerProposals/heading', ['doing' => $recording]) ?>

            <?php
            ?>
            <?= $this->Form->create($customerProposal) ?>
            <fieldset>
                <p><?= $again
                    ? __(
                        'They went out on {0}. Recording it again puts the new day in its place.',
                        $customerProposal->sent_date,
                    )
                    : __('Once this is recorded, the customer proposal may no longer be'
                        . ' changed. A correction is a new customer proposal.') ?></p>
                <br>
                <?php
                echo $this->Form->control('sent_date', [
                    'default' => Date::now(),
                    'label' => __('Sent To The Customer'),
                    'help' => __('The day the proposal was sent to the customer.'),
                ]);
                echo $this->Form->control('delivery_type', [
                    'options' => $deliveryTypes,
                    'empty' => true,
                    'label' => __('Sent By'),
                ]);
                ?>
            </fieldset>
            <?= $this->element('common/also_in_the_round', [
                'saying' => __('These get the same day, because they went out together.'),
            ]) ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
