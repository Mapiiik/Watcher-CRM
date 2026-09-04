<?php
/**
 * The body of the proposal form, shared by adding one, changing one and taking its snapshot again.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var \App\Model\Entity\Contract|null $contract
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $versions
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $services
 * @var array<\App\Model\Entity\Billing> $billings
 * @var array<string> $questions
 * @var array<string, string> $wording
 * @var array<string> $contractNumbers
 */

use App\Contracts\Proposal\ProposalForm;
use Cake\I18n\Date;

$changes = $contractVersionProposal->isNew()
    ? null
    : $contractVersionProposal->proposedChanges();
$acted_on = $changes?->billingsByBillingId() ?? [];
?>
<datalist id="contract-numbers-to-be-terminated">
    <?php foreach ($contractNumbers as $contractNumber) : ?>
        <option value="<?= h($contractNumber) ?>">
    <?php endforeach; ?>
</datalist>
<fieldset>
    <legend><?= __('What the papers are for') ?></legend>
    <?php
    if (!isset($contract_id)) {
        echo $this->Form->control('contract_id', [
            'options' => $contracts,
            'empty' => true,
            'onchange' => $this::REFRESH_ON_CHANGE,
        ]);
        $this->Form->unlockField('refresh');
    }
    echo $this->Form->control('contract_version_id', [
        'options' => $versions,
        'empty' => true,
        'label' => __('Contract Version'),
    ]);
    echo $this->Form->control('effective_from', [
        'default' => Date::now()->addMonths(1)->firstOfMonth(),
        'label' => __('Effective From'),
    ]);
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
    <legend><?= __('Billings') ?></legend>
    <?php if ($billings === []) : ?>
        <p><?= __('There is nothing billed for on this contract yet.') ?></p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Billing From') ?></th>
                    <th><?= __('What is to happen') ?></th>
                    <th><?= __('Service') ?></th>
                    <th><?= __('Quantity') ?></th>
                    <th><?= __('Price') ?></th>
                    <th><?= __('Billing Until') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($billings as $billing) : ?>
                <?php
                $id = (string)$billing->id;
                $line = $acted_on[$id] ?? null;
                $action = match (true) {
                    $line === null => ProposalForm::KEEP,
                    $line->terminatesOnly() => ProposalForm::END,
                    default => ProposalForm::REPLACE,
                };
    ?>
                <tr>
                    <td><?= h($billing->name) ?></td>
                    <td><?= h($billing->billing_from) ?></td>
                    <td>
                        <?= $this->Form->select("lines.{$id}.action", [
                            ProposalForm::KEEP => __('Keep'),
                            ProposalForm::REPLACE => __('Change'),
                            ProposalForm::END => __('End'),
                        ], ['value' => $action, 'label' => false]) ?>
                    </td>
                    <td>
                        <?= $this->Form->select("lines.{$id}.service_id", $services, [
                            'empty' => true,
                            'value' => $line->service_id ?? $billing->service_id,
                            'label' => false,
                        ]) ?>
                    </td>
                    <td>
                        <?= $this->Form->number("lines.{$id}.quantity", [
                            'value' => $line->quantity ?? $billing->quantity,
                            'label' => false,
                        ]) ?>
                    </td>
                    <td>
                        <?= $this->Form->text("lines.{$id}.price", [
                            'value' => $line?->price?->toString() ?? $billing->price?->toString(),
                            'label' => false,
                            'placeholder' => __('Price list'),
                        ]) ?>
                    </td>
                    <td>
                        <?= $this->Form->date("lines.{$id}.billing_until", [
                            'value' => $line?->billing_until,
                            'label' => false,
                            'empty' => true,
                        ]) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h4><?= __('Add a billing') ?></h4>
    <?php
    // Two rows are enough for the usual change; more than that is a second proposal or a second
    // pass over this one.
    $additions = array_values(array_filter(
        $changes->billings ?? [],
        fn($line): bool => $line->isAddition(),
    ));
    $rows = max(2, count($additions) + 1);
    for ($row = 0; $row < $rows; $row++) :
        $line = $additions[$row] ?? null;
        ?>
        <div class="row">
            <div class="column">
                <?= $this->Form->select("additions.{$row}.service_id", $services, [
                    'empty' => true,
                    'value' => $line?->service_id,
                    'label' => $row === 0 ? __('Service') : false,
                ]) ?>
            </div>
            <div class="column">
                <?= $this->Form->text("additions.{$row}.text", [
                    'value' => $line?->text,
                    'label' => $row === 0 ? __('Text') : false,
                ]) ?>
            </div>
            <div class="column">
                <?= $this->Form->number("additions.{$row}.quantity", [
                    'value' => $line->quantity ?? 1,
                    'label' => $row === 0 ? __('Quantity') : false,
                ]) ?>
            </div>
            <div class="column">
                <?= $this->Form->text("additions.{$row}.price", [
                    'value' => $line?->price?->toString(),
                    'label' => $row === 0 ? __('Price') : false,
                    'placeholder' => __('Price list'),
                ]) ?>
            </div>
        </div>
    <?php endfor; ?>
</fieldset>

<fieldset>
    <legend><?= __('The contract version and the contract') ?></legend>
    <p><?= __('Only what is ticked here is changed; the rest is left as it stands.') ?></p>
    <?php
    foreach (['valid_until', 'obligation_until'] as $field) {
        $named = $changes?->version->names($field) ?? false;
        echo $this->Form->control("version_named.{$field}", [
            'type' => 'checkbox',
            'checked' => $named,
            'label' => $field === 'valid_until'
                ? __('Change the day the version stops being valid')
                : __('Change the day the obligation runs out'),
        ]);
        echo $this->Form->control("version.{$field}", [
            'type' => 'date',
            'empty' => true,
            'value' => $named ? $changes?->version->get($field) : null,
            'label' => false,
        ]);
    }

    $ends = $changes?->contract->names('termination_date') ?? false;
    echo $this->Form->control('contract_named.termination_date', [
        'type' => 'checkbox',
        'checked' => $ends,
        'label' => __('Terminate the contract'),
    ]);
    echo $this->Form->control('contract.termination_date', [
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
        <?= $this->Form->control("acknowledgements.{$question}", [
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
echo $this->Form->control('acknowledgements.fixed_term', [
    'type' => 'checkbox',
    'checked' => $contractVersionProposal->confirmations()->confirms('fixed_term'),
    'label' => __('This is a fixed-term contract, and the obligation runs to the end of it.'),
]);
