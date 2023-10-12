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
            <?= $this->AuthLink->link(
                __('Edit Tax Rate'),
                ['action' => 'edit', $taxRate->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Tax Rate'),
                ['action' => 'delete', $taxRate->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $taxRate->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Tax Rates'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Tax Rate'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taxRates view content">
            <h3><?= h($taxRate->name) ?></h3>
            <div class="row">
                <div class="column">
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
                            <th><?= __('Accounting Assignment Code') ?></th>
                            <td><?= h($taxRate->accounting_assignment_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Account Code') ?></th>
                            <td><?= h($taxRate->bank_account_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activity Code') ?></th>
                            <td><?= h($taxRate->activity_code) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
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
                            <td><?= $taxRate->__isset('creator') ? $this->Html->link(
                                $taxRate->creator->username,
                                [
                                    'controller' => 'AppUsers',
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
                            <td><?= $taxRate->__isset('modifier') ? $this->Html->link(
                                $taxRate->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $taxRate->modifier->id,
                                ]
                            ) : h($taxRate->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Customers') ?></h4>
                <?php if (!empty($taxRate->customers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Company') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('First Name') ?></th>
                            <th><?= __('Last Name') ?></th>
                            <th><?= __('Suffix') ?></th>
                            <th><?= __('Contracts') ?></th>
                            <th><?= __('IP Addresses') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($taxRate->customers as $customer) : ?>
                        <tr>
                            <td><?= h($customer->number) ?></td>
                            <td><?= h($customer->company) ?></td>
                            <td><?= h($customer->title) ?></td>
                            <td><?= h($customer->first_name) ?></td>
                            <td><?= h($customer->last_name) ?></td>
                            <td><?= h($customer->suffix) ?></td>
                            <td>
                                <?php foreach ($customer->contracts as $contract) {
                                    echo h($contract->number) . '<br>';
                                } ?>
                            </td>
                            <td>
                                <?php foreach ($customer->ips as $ip) {
                                    echo h($ip->ip) . '<br>';
                                } ?>
                            </td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Customers', 'action' => 'view', $customer->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Customers', 'action' => 'edit', $customer->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
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
