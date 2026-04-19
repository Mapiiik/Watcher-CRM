<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AccountingProfile $accountingProfile
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Accounting Profile'),
                ['action' => 'edit', $accountingProfile->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Accounting Profile'),
                ['action' => 'delete', $accountingProfile->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $accountingProfile->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Accounting Profiles'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Accounting Profile'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="accountingProfiles view content">
            <h3><?= h($accountingProfile->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($accountingProfile->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Vat Rate') ?></th>
                            <td><?= $this->Number->format($accountingProfile->vat_rate) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Reverse Charge') ?></th>
                            <td><?= $accountingProfile->reverse_charge ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice With Items') ?></th>
                            <td><?= $accountingProfile->invoice_with_items ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Accounting Assignment Code') ?></th>
                            <td><?= h($accountingProfile->accounting_assignment_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Account Code') ?></th>
                            <td><?= h($accountingProfile->bank_account_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activity Code') ?></th>
                            <td><?= h($accountingProfile->activity_code) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $accountingProfile]) ?>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Customers') ?></h4>
                <?php if (!empty($accountingProfile->customers)) : ?>
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
                        <?php foreach ($accountingProfile->customers as $customer) : ?>
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
                                <?php foreach ($customer->ip_addresses as $ipAddress) {
                                    echo h($ipAddress->ip_address) . '<br>';
                                } ?>
                            </td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Customers', 'action' => 'view', $customer->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Customers', 'action' => 'edit', $customer->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Customers', 'action' => 'delete', $customer->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id)],
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
