<?php
/**
 * Names the customer: their number, and their name underneath it.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var string|null $doing What the page holding this is about the customer, if not the customer.
 */

echo $this->record(
    __('Customer No.'),
    (string)$customer->number,
    (string)$customer->name,
    $doing ?? null,
);
