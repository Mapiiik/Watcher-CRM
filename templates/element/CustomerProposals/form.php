<?php
/**
 * The fields of a round, shared by adding one and changing one.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<string, string> $purposes
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string, string> $customers
 */
?>
<fieldset>
    <?= $this->legend($customerProposal->isNew()
        ? __('Add Customer Proposal')
        : __('Edit Customer Proposal')) ?>
    <?php
    // Which customer is settled by the page it was opened from, and a round never moves to
    // somebody else - the papers went to one person.
    if ($customerProposal->isNew() && $customerProposal->customer_id === null) {
        echo $this->Form->control('customer_id', [
            'options' => $customers,
            'empty' => true,
            'label' => __('Customer'),
            'required' => true,
        ]);
    } else {
        echo $this->Form->hidden('customer_id');
    }

    // A round may be for nothing of the customer's own and only hold its contracts' papers, so
    // this may be left alone.
    echo $this->Form->control('purpose', [
        'options' => $purposes,
        'empty' => __('Nothing'),
        'label' => __('Purpose'),
        'required' => false,
        'help' => __('What the papers ask of the customer themselves. Left alone, the round only'
            . ' holds the papers of their contracts.'),
    ]);
    echo $this->Form->control('effective_from', [
        'label' => __('Effective From'),
        'help' => __('The day the papers speak about - today, or the day of whatever they go out'
            . ' with.'),
    ]);
    echo $this->Form->control('note', [
        'label' => __('Note'),
        'help' => __('For the office. It does not reach the paper.'),
    ]);

    if ($customerProposal->isNew()) {
        echo '<p>' . __('Papers of the contracts go out in this round as well. They are drawn up'
            . ' one at a time, on the proposal of the contract they are about.') . '</p>';
    }
    ?>
</fieldset>
