<?php
/**
 * The contract a finding sits on, as one cell of a check's listing.
 *
 * Every check names the contract the same way, and on a contract's own page none of them does -
 * every row there is about that contract already. Written once so that the two stay in step.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract|null $contract
 * @var bool $contract_column
 */

if (!$contract_column) {
    return;
}
?>
<td>
    <?php if ($contract !== null) : ?>
        <?= $this->Html->link($contract->number ?? '--', [
            'controller' => 'Contracts',
            'action' => 'view',
            $contract->id,
            'customer_id' => $contract->customer_id,
        ]) ?>
    <?php endif ?>
</td>
