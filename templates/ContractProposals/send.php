<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var array<int|string, string> $deliveryMethods
 */

use Cake\I18n\Date;

// Papers do go out more than once - by another means, or after the first attempt came back. What
// they stand on was settled the first time; the day is what a second sending moves.
$again = $contractProposal->hasBeenSent();
$recording = $again ? __('Record the Sending Again') : __('Record the Sending');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals form content">
            <?= $this->element('ContractProposals/heading', ['doing' => $recording]) ?>

<?php
$saying = $again
    ? __(
        'They went out on {0}. Recording it again puts the new day in its place - which is what'
        . ' sending them again does, and the customer has from that day to answer.',
        $contractProposal->sent_date,
    )
    : __(
        'Once this is recorded, what the papers stand on is settled: the snapshot, what the'
        . ' proposal asks for and what was confirmed can no longer be changed. A correction is a'
        . ' new proposal.',
    );
?>
            <?= $this->Form->create($contractProposal) ?>
            <fieldset>
                <p><?= $saying ?></p>
                <br>
                <?php
                echo $this->Form->control('sent_date', [
                    'default' => Date::now(),
                    'label' => __('Sent To The Customer'),
                    'help' => __('The day the papers went out to the customer.'),
                ]);
                echo $this->Form->control('delivery_type', [
                    'options' => $deliveryMethods,
                    'empty' => true,
                    'label' => __('Sent By'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
