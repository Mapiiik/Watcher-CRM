<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractProposal> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;
?>
<p>
    <?= __(
        'The customer has agreed to something and the records still say the old thing, so the'
        . ' service runs and is invoiced on the old terms until somebody applies the changes.',
    ) ?>
</p>
<?= $this->element('ContractChecks/proposal_table', [
    'records' => $records,
    'contract_column' => $contract_column,
    'customer_column' => $customer_column,
    'dates' => ['concluded', 'sent'],
    'steps' => ['apply'],
]) ?>
