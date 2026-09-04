<?php
/**
 * The head of a proposal: what the papers are for, and what they say about the version and the
 * contract. What is billed for is not here - each line of that is edited on a page of its own,
 * from the proposal's own table, the same way a billing on a contract is.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var \App\Model\Entity\Contract|null $contract
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $versions
 * @var array<string> $questions
 * @var array<string, string> $wording
 * @var array<string> $contractNumbers
 * @var bool $effectiveDateIsItsOwn
 */

$changes = $contractVersionProposal->isNew()
    ? null
    : $contractVersionProposal->proposedChanges();
?>
<datalist id="contract-numbers-to-be-terminated">
    <?php foreach ($contractNumbers as $contractNumber) : ?>
        <option value="<?= h($contractNumber) ?>">
    <?php endforeach; ?>
</datalist>
<fieldset>
    <legend><?= __('What the papers are for') ?></legend>
    <?php
    // Both the contract and the version redraw the form when they change, and the field they add
    // to do it is not one the form declared - so it is unlocked whichever of them is on the page.
    $this->Form->unlockField('refresh');

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

    // On an amendment the day it takes effect is its own; otherwise it is the day the version
    // takes effect, and asking twice would only invite the two to disagree.
    if ($effectiveDateIsItsOwn) {
        echo $this->Form->control('effective_from', [
            'label' => __('Effective date of the amendment'),
        ]);
    } else {
        echo '<p>' . __('These papers take effect with the contract version they are for.') . '</p>';
    }

    echo $this->Form->control('terminates_contract_version_id', [
        'options' => $versions,
        'empty' => true,
        'label' => __('Terminates Contract Version'),
    ]);
    echo $this->Form->control('terminated_contract_number', [
        'label' => __('Number of the contract being terminated'),
        'list' => 'contract-numbers-to-be-terminated',
    ]);
    echo $this->Form->control('note');
    ?>
</fieldset>

<fieldset>
    <legend><?= __('The contract version and the contract') ?></legend>
    <p><?= __('Only what is ticked here is changed; the rest is left as it stands.') ?></p>
    <?php
    foreach (['valid_until', 'obligation_until'] as $field) {
        $named = $changes?->version->names($field) ?? false;
        echo $this->Form->control("version_change_named.{$field}", [
            'type' => 'checkbox',
            'checked' => $named,
            'label' => $field === 'valid_until'
                ? __('Change the day the version stops being valid')
                : __('Change the day the obligation runs out'),
        ]);
        echo $this->Form->control("version_change.{$field}", [
            'type' => 'date',
            'empty' => true,
            'value' => $named ? $changes?->version->get($field) : null,
            'label' => false,
        ]);
    }

    $ends = $changes?->contract->names('termination_date') ?? false;
    echo $this->Form->control('contract_change_named.termination_date', [
        'type' => 'checkbox',
        'checked' => $ends,
        'label' => __('Terminate the contract'),
    ]);
    echo $this->Form->control('contract_change.termination_date', [
        'type' => 'date',
        'empty' => true,
        'value' => $ends ? $changes?->contract->get('termination_date') : null,
        'label' => false,
    ]);
    ?>
</fieldset>

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
// An end date on a version is also how a superseded one is recorded, so printing it as a fixed-term
// contract is said out loud rather than assumed.
echo $this->Form->control('confirmations.fixed_term', [
    'type' => 'checkbox',
    'checked' => $contractVersionProposal->confirmations()->confirms('fixed_term'),
    'label' => __('This is a fixed-term contract, and the obligation runs to the end of it.'),
]);
