<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaxRate $taxRate
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Tax Rate'),
                ['action' => 'edit', $taxRate->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Tax Rate'),
                ['action' => 'delete', $taxRate->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $taxRate->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Tax Rates'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Tax Rate'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="taxRates view content">
            <h3><?= h($taxRate->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($taxRate->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Vat Rate') ?></th>
                    <td><?= $this->Number->format($taxRate->vat_rate) ?></td>
                </tr>
                <tr>
                    <th><?= __('Reverse Charge') ?></th>
                    <td><?= $taxRate->reverse_charge ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($taxRate->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($taxRate->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $taxRate->has('creator') ? $this->Html->link(
                        $taxRate->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $taxRate->creator->id,
                        ]
                    ) : h($taxRate->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($taxRate->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $taxRate->has('modifier') ? $this->Html->link(
                        $taxRate->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $taxRate->modifier->id,
                        ]
                    ) : h($taxRate->modified_by) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Customers') ?></h4>
                <?php if (!empty($taxRate->customers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Company') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('First Name') ?></th>
                            <th><?= __('Last Name') ?></th>
                            <th><?= __('Suffix') ?></th>
                            <th><?= __('Date Of Birth') ?></th>
                            <th><?= __('Ic') ?></th>
                            <th><?= __('Dic') ?></th>
                            <th><?= __('Contracts') ?></th>
                            <th><?= __('Ips') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($taxRate->customers as $customer) : ?>
                        <tr>
                            <td><?= h($customer->company) ?></td>
                            <td><?= h($customer->title) ?></td>
                            <td><?= h($customer->first_name) ?></td>
                            <td><?= h($customer->last_name) ?></td>
                            <td><?= h($customer->suffix) ?></td>
                            <td><?= h($customer->date_of_birth) ?></td>
                            <td><?= h($customer->ic) ?></td>
                            <td><?= h($customer->dic) ?></td>
                            <td>
                                <?php foreach ($customer->contracts as $contract) {
                                    echo h($contract->number) . '<br />';
                                } ?>
                            </td>
                            <td>
                                <?php foreach ($customer->ips as $ip) {
                                    echo h($ip->ip) . '<br />';
                                } ?>
                            </td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['controller' => 'Customers', 'action' => 'view', $customer->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['controller' => 'Customers', 'action' => 'edit', $customer->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Customers', 'action' => 'delete', $customer->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
