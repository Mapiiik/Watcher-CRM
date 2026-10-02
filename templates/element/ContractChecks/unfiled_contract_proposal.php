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
        'The signature is recorded and the signed documents were never filed. Nothing is held'
        . ' up by it, but there is nothing to show for what was agreed either.',
    ) ?>
</p>
<?= $this->element('ContractChecks/proposal_table', [
    'records' => $records,
    'contract_column' => $contract_column,
    'customer_column' => $customer_column,
    'dates' => ['concluded'],
    'steps' => ['documents'],
]) ?>
