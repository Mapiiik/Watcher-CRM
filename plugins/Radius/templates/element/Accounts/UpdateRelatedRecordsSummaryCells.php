<?php
/**
 * The customer, contract and account a change is about, as cells of the change log.
 *
 * @var \App\View\AppView $this
 * @var \Radius\Updater\ChangeLog\Change $change
 */
?>
<td><?= $this->Html
    ->link(
        $change->getCustomer()->name,
        [
            '_full' => true,
            'plugin' => null,
            'controller' => 'Customers',
            'action' => 'view',
            $change->getCustomer()->id,
        ],
    ) ?></td>
<td><?= $this->Html
    ->link(
        $change->getContract()->name,
        [
            '_full' => true,
            'plugin' => null,
            'controller' => 'Contracts',
            'action' => 'view',
            $change->getContract()->id,
            'customer_id' => $change->getContract()->customer_id,
        ],
    ) ?></td>
<td><?= $this->Html
    ->link(
        $change->getAccount()->username,
        [
            '_full' => true,
            'plugin' => 'Radius',
            'controller' => 'Accounts',
            'action' => 'view',
            'customer_id' => $change->getAccount()->customer_id,
            'contract_id' => $change->getAccount()->contract_id,
            $change->getAccount()->id,
        ],
    ) ?></td>
