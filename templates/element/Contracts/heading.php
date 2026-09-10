<?php
/**
 * Names the contract: its number, and a line underneath saying what it is and where it is.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<?= __('Contract No.') ?><h3><?= h($contract->number) ?></h3>
<h5><?= h(
    ($contract->service_type !== null ? $contract->service_type->name : '')
    . ($contract->installation_address !== null ? ' - ' . $contract->installation_address->address : ''),
) ?></h5>
