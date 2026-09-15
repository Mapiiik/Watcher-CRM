<?php
/**
 * Names the contract: its number, and a line underneath saying what it is and where it is.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var string|null $doing What the page holding this is about the contract, if not the contract.
 * @var bool $withName Whether the line goes underneath, where the page does not say it again.
 */

$named = $withName ?? true;

$says = ($contract->service_type !== null ? $contract->service_type->name : '')
    . ($contract->installation_address !== null ? ' - ' . $contract->installation_address->address : '');

echo $this->record(
    __('Contract'),
    (string)$contract->number,
    $named ? $says : null,
    $doing ?? null,
);
