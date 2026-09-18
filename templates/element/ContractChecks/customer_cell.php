<?php
/**
 * The customer a finding sits on, as one cell of a check's listing.
 *
 * The companion of the contract's cell: on the overview a contract number alone says little about
 * whom to call, and on the customer's own page every row is about them already.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer|null $customer
 * @var bool $customer_column
 */

if (!$customer_column) {
    return;
}
?>
<td class="dashboard-wrap">
    <?php if ($customer !== null) : ?>
        <?= $this->Html->link(
            $customer->name_for_lists,
            ['controller' => 'Customers', 'action' => 'view', $customer->id],
        ) ?>
    <?php endif ?>
</td>
