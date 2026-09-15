<?php
/**
 * Names the customer: their number, and their name underneath it.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var string|null $doing What the page holding this is about the customer, if not the customer.
 * @var bool $withName Whether the name goes underneath, where the page does not say it again.
 */

$named = $withName ?? true;

echo $this->record(
    __('Customer'),
    (string)$customer->number,
    $named ? (string)$customer->name : null,
    $doing ?? null,
);
