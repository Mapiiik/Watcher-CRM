<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var array<int|string, string> $deliveryMethods
 */

use Cake\I18n\Date;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersionProposals form content">
            <?= $this->element('ContractVersionProposals/heading') ?>

<?php
// Papers do go out more than once - by another means, or after the first attempt came back. What
// they stand on was settled the first time; the day is what a second sending moves.
$again = $contractVersionProposal->hasBeenSent();
$saying = $again
    ? __(
        'They went out on {0}. Recording it again puts the new day in its place - which is what'
        . ' sending them again does, and the customer has from that day to answer.',
        $contractVersionProposal->sent_date,
    )
    : __(
        'Once this is recorded, what the papers stand on is settled: the snapshot, what the'
        . ' proposal asks for and what was confirmed can no longer be changed. A correction is a'
        . ' new proposal.',
    );
?>
            <?= $this->Form->create($contractVersionProposal) ?>
            <fieldset>
                <legend><?= $again
                    ? __('Record the Sending Again')
                    : __('Record the Sending') ?></legend>
                <p><?= $saying ?></p>
                <?php
                echo $this->Form->control('sent_date', [
                    'default' => Date::now(),
                    'label' => __('Sent To The Customer'),
                    'help' => __('The day the papers went out to the customer.'),
                ]);
                echo $this->Form->control('sent_by', [
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
