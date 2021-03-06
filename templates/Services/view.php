<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Service $service
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Service'),
                ['action' => 'edit', $service->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Service'),
                ['action' => 'delete', $service->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $service->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Services'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Service'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="services view content">
            <h3><?= h($service->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($service->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Service Type') ?></th>
                    <td><?= $service->has('service_type') ? $this->Html->link(
                        $service->service_type->name,
                        ['controller' => 'ServiceTypes', 'action' => 'view', $service->service_type->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Queue') ?></th>
                    <td><?= $service->has('queue') ? $this->Html->link(
                        $service->queue->name,
                        ['controller' => 'Queues', 'action' => 'view', $service->queue->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Price') ?></th>
                    <td><?= $this->Number->format($service->price) ?></td>
                </tr>
                <tr>
                    <th><?= __('Not For New Customers') ?></th>
                    <td><?= $service->not_for_new_customers ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($service->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($service->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $service->has('creator') ? $this->Html->link(
                        $service->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $service->creator->id,
                        ]
                    ) : h($service->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($service->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $service->has('modifier') ? $this->Html->link(
                        $service->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $service->modifier->id,
                        ]
                    ) : h($service->modified_by) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Billings') ?></h4>
                <?php if (!empty($service->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Fixed Discount') ?></th>
                            <th><?= __('Percentage Discount') ?></th>
                            <th><?= __('Total Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Separate Invoice') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($service->billings as $billing) : ?>
                        <tr style="<?= $billing->style ?>">
                            <td><?= $this->Number->format($billing->id) ?></td>
                            <td><?= $billing->has('customer') ?
                                $this->Html->link(
                                    $billing->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $billing->customer->id]
                                ) : '' ?></td>
                            <td><?= $billing->has('contract') ?
                                $this->Html->link(
                                    $billing->contract->number,
                                    ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]
                                ) : '' ?></td>
                            <td><?= h($billing->text) ?></td>
                            <td><?= h($billing->quantity) ?></td>
                            <td><?= h($billing->price) ?><?= $billing->has('service') ?
                                ' (' . h($billing->service->price) . ')' : '' ?></td>
                            <td><?= h($billing->fixed_discount) ?></td>
                            <td><?= h($billing->percentage_discount) ?></td>
                            <td><?= $this->Number->currency($billing->total_price) ?></td>
                            <td><?= h($billing->billing_from) ?></td>
                            <td><?= h($billing->billing_until) ?></td>
                            <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
                            <td><?= $billing->separate_invoice ? __('Yes') : __('No'); ?></td>
                            <td><?= h($billing->note) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Billings', 'action' => 'view', $billing->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Billings', 'action' => 'edit', $billing->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Billings', 'action' => 'delete', $billing->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id)]
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
