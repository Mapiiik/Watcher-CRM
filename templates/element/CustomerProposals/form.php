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
        'help' => __('What the customer proposal itself is for. If left empty, it only holds'
            . ' the proposals of the customer\'s contracts.'),
    ]);
    echo $this->Form->control('effective_from', [
        'label' => __('Effective From'),
        'help' => __('The day the proposal takes effect, usually today or the day of the'
            . ' changes it is sent with.'),
    ]);
    echo $this->Form->control('note', [
        'label' => __('Note'),
        'help' => __('For the office. It is not printed on the documents.'),
    ]);

    if ($customerProposal->isNew()) {
        echo '<p>' . __('The customer proposal also holds contract proposals. They are created'
            . ' one at a time, each for its own contract.') . '</p>';
    }
    ?>
</fieldset>
