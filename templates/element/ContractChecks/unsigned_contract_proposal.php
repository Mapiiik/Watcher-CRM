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
        'The proposal was sent and has not come back signed. No changes are applied to the'
        . ' records until it does.',
    ) ?>
</p>
<?= $this->element('ContractChecks/proposal_table', [
    'records' => $records,
    'contract_column' => $contract_column,
    'customer_column' => $customer_column,
    'dates' => ['sent'],
    'steps' => ['conclude'],
]) ?>
