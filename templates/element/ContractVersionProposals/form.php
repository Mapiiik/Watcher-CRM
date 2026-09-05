<?php
/**
 * The head of a proposal: what the papers are for, and what they say about the version and the
 * contract. What is billed for is not here - each line of that is edited on a page of its own,
 * from the proposal's own table, the same way a billing on a contract is.
 *
 * What is asked follows the purpose. Asking everything at once was how this began, and it put an
 * agreement to end a contract behind two checkboxes, two dates that had to match, and a question
 * about a fixed term that an ending is not.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var \App\Model\Entity\Contract|null $contract
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $versions
 * @var array<string> $questions
 * @var array<string, string> $wording
 * @var array<string, string> $contractNumbers
 * @var array<string, string> $purposes
 * @var \App\Model\Enum\ProposalPurpose $purpose
 * @var \Cake\I18n\Date|null $effectiveFromDefault
 */

use App\Model\Enum\ProposalPurpose;

$changes = $contractVersionProposal->isNew()
    ? null
    : $contractVersionProposal->proposedChanges();

$ending = $purpose === ProposalPurpose::Termination;
$endsOn = $changes?->version->names('valid_until') ?? false
    ? $changes->version->get('valid_until')
    : null;
?>
<fieldset>
    <legend><?= __('What the papers are for') ?></legend>
    <?php
    // The purpose, the contract and the version all redraw the form when they change, and the
    // field they add to do it is not one the form declared - so it is unlocked whichever of them
    // is on the page.
    $this->Form->unlockField('refresh');

    echo $this->Form->control('purpose', [
        'options' => $purposes,
        'label' => __('Purpose'),
        'onchange' => $this::REFRESH_ON_CHANGE,
    ]);

    if (!isset($contract_id)) {
        echo $this->Form->control('contract_id', [
            'options' => $contracts,
            'empty' => true,
            'onchange' => $this::REFRESH_ON_CHANGE,
        ]);
    }
    echo $this->Form->control('contract_version_id', [
        'options' => $versions,
        'empty' => true,
        'label' => __('Contract Version'),
        'onchange' => $this::REFRESH_ON_CHANGE,
    ]);

    // A change is agreed while the version runs, so it says its own day. A new contract starts
    // with its version, and an ending says the day it ends on - both are worked out rather than
    // asked, because asking twice would only invite the two to disagree.
    if ($purpose->asksForItsOwnDay()) {
        echo $this->Form->control('effective_from', [
            'label' => __('Effective From'),
            // Left empty it follows the version, so it is not filled in ahead of time: a day put
            // there for the operator would stay behind when they chose another version.
            'required' => false,
            'help' => $effectiveFromDefault === null
                ? __('The day these papers start to apply.')
                : __(
                    'The day these papers start to apply. Empty takes the day the version does,'
                    . ' {0}.',
                    $effectiveFromDefault,
                ),
        ]);
    } elseif (!$ending) {
        echo '<p>' . __('These papers take effect with the contract version they are for.') . '</p>';
    }

    // Only a new contract may end an earlier version of the same contract, which is the one paper
    // that does both at once. A change leaves the version where it is, by definition.
    if ($purpose === ProposalPurpose::NewContract) {
        echo $this->Form->control('terminates_contract_version_id', [
            'options' => $versions,
            'empty' => true,
            'label' => __('Terminates Contract Version'),
        ]);
    }

    // The number is what goes on the paper of anything that ends something, and both of those do.
    if ($purpose !== ProposalPurpose::ServiceChange) {
        echo $this->Form->control('terminated_contract_number', [
            'options' => $contractNumbers,
            'empty' => true,
            'label' => __('Number of the contract being terminated'),
        ]);
    }
    echo $this->Form->control('note');
    ?>
</fieldset>

<?php if ($ending) : ?>
<fieldset>
    <legend><?= __('When it ends') ?></legend>
    <?php
    echo $this->Form->control('ends_on', [
        'type' => 'date',
        'empty' => true,
        'value' => $endsOn,
        'label' => __('Last day of the service'),
        'help' => __('The version stops being valid on this day, and so does what is billed for.'),
    ]);
    echo $this->Form->control('version_only', [
        'type' => 'checkbox',
        'checked' => ($changes?->version->endsTheVersion() ?? false)
            && !$changes->contract->endsTheContract(),
        'label' => __('End this version only, and leave the contract running'),
        'help' => __('For an agreement to end one version with another to follow it.'),
    ]);
    ?>
</fieldset>
<?php else : ?>
<fieldset>
    <legend><?= __('The contract version') ?></legend>
    <p><?= __('Only what is ticked here is changed. The rest is left as it stands.') ?></p>
    <?php
    foreach (['valid_until', 'obligation_until'] as $field) {
        $named = $changes?->version->names($field) ?? false;
        $id = 'version-change-' . str_replace('_', '-', $field);

        // The box turns its own day on and off, the way the dates on a contract version do.
        echo $this->Form->control("version_change_named.{$field}", [
            'type' => 'checkbox',
            'checked' => $named,
            'label' => $field === 'valid_until'
                ? __('Change the day the version stops being valid')
                : __('Change the day the obligation runs out'),
            'onclick' => sprintf('document.getElementById("%s").disabled = !this.checked;', $id),
        ]);

        // A disabled field sends nothing, so an empty one is sent in its place - and form
        // protection is told, because it counts what the markup declared.
        echo $this->Form->hidden("version_change.{$field}", ['value' => '']);
        echo $this->Form->control("version_change.{$field}", [
            'id' => $id,
            'type' => 'date',
            'empty' => true,
            'value' => $named ? $changes?->version->get($field) : null,
            'label' => false,
            'disabled' => !$named,
        ]);
        $this->Form->unlockField("version_change.{$field}");
    }
    ?>
</fieldset>
<?php endif; ?>

<?php if ($questions !== []) : ?>
<fieldset>
    <legend><?= __('Before the papers are drawn up') ?></legend>
    <?php foreach ($questions as $question) : ?>
        <?= $this->Form->control("confirmations.{$question}", [
            'type' => 'checkbox',
            'checked' => $contractVersionProposal->confirmations()->confirms($question),
            'label' => $wording[$question] ?? $question,
        ]) ?>
    <?php endforeach; ?>
</fieldset>
<?php endif; ?>

<?php
// An end date on a version is also how an ending and a superseded version are recorded, so a paper
// meant to run for a fixed term is said out loud rather than assumed. An ending never asks.
if (!$ending) {
    echo $this->Form->control('confirmations.fixed_term', [
        'type' => 'checkbox',
        'checked' => $contractVersionProposal->confirmations()->confirms('fixed_term'),
        'label' => __('This is a fixed-term contract, and the obligation runs to the end of it.'),
    ]);
}
