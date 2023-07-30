<?php
use Cake\Collection\Collection;

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
    <div class="column column-90">
        <div class="services view content">
            <h3><?= h($service->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($service->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <td><?= $service->__isset('service_type') ? $this->Html->link(
                                $service->service_type->name,
                                ['controller' => 'ServiceTypes', 'action' => 'view', $service->service_type->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Queue') ?></th>
                            <td><?= $service->__isset('queue') ? $this->Html->link(
                                $service->queue->name,
                                ['controller' => 'Queues', 'action' => 'view', $service->queue->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Price') ?></th>
                            <td><?= $service->price === null ? '' : $this->Number->currency($service->price) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Not For New Customers') ?></th>
                            <td><?= $service->not_for_new_customers ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
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
                            <td><?= $service->__isset('creator') ? $this->Html->link(
                                $service->creator->username,
                                [
                                    'controller' => 'AppUsers',
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
                            <td><?= $service->__isset('modifier') ? $this->Html->link(
                                $service->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $service->modifier->id,
                                ]
                            ) : h($service->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Billings') ?></h4>
                <?php if (!empty($service->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
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
                            <td><?= $billing->__isset('customer') ?
                                $this->Html->link(
                                    $billing->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $billing->customer->id]
                                ) : '' ?></td>
                            <td><?= $billing->__isset('customer') ? h($billing->customer->number) : '' ?></td>
                            <td><?= $billing->__isset('contract') ?
                                $this->Html->link(
                                    $billing->contract->number ?? '--',
                                    ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]
                                ) : '' ?></td>
                            <td><?= h($billing->text) ?></td>
                            <td><?= h($billing->quantity) ?></td>
                            <td><?= h($billing->price) ?><?= $billing->__isset('service') ?
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
                <div>
                    <?= __('Total sum of active') . ': ' . $this->Number->currency(
                        (new Collection($service->billings))
                            ->filter(function ($billing) {
                                return $billing->active;
                            })
                            ->sumOf('total_price')
                    ) ?><br>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
