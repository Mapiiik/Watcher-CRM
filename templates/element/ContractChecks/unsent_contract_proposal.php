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
        'The proposal was created and never sent. The day it takes effect comes whether or not'
        . ' the customer has seen it.',
    ) ?>
</p>
<?= $this->element('ContractChecks/proposal_table', [
    'records' => $records,
    'contract_column' => $contract_column,
    'customer_column' => $customer_column,
    'dates' => ['created'],
    'steps' => ['documents', 'send'],
]) ?>
