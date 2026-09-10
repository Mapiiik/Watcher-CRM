<?php
/**
 * Names the customer: their number, and their name underneath it.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 */
?>
<?= __('Customer No.') ?><h3><?= h($customer->number) ?></h3>
<h5><?= h($customer->name) ?></h5>
