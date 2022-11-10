<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Queue $queue
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Queue'),
                ['action' => 'edit', $queue->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Queue'),
                ['action' => 'delete', $queue->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $queue->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Queues'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Queue'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="queues view content">
            <h3><?= h($queue->name) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($queue->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Caption') ?></th>
                            <td><?= h($queue->caption) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Fup') ?></th>
                            <td><?= $this->Number->format($queue->fup) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Limit') ?></th>
                            <td><?= $this->Number->format($queue->limit) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Overlimit Fragment') ?></th>
                            <td><?= $this->Number->format($queue->overlimit_fragment) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Overlimit Cost') ?></th>
                            <td><?= $this->Number->currency($queue->overlimit_cost) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Up') ?></th>
                            <td><?= $this->Number->format($queue->speed_up) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Down') ?></th>
                            <td><?= $this->Number->format($queue->speed_down) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Cto Category') ?></th>
                            <td><?= h($queue->cto_category) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($queue->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($queue->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $queue->has('creator') ? $this->Html->link(
                                $queue->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $queue->creator->id,
                                ]
                            ) : h($queue->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($queue->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $queue->has('modifier') ? $this->Html->link(
                                $queue->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $queue->modifier->id,
                                ]
                            ) : h($queue->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Services') ?></h4>
                <?php if (!empty($queue->services)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Service Type') ?></th>
                            <th><?= __('Not For New Customers') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($queue->services as $service) : ?>
                        <tr>
                            <td><?= h($service->name) ?></td>
                            <td><?= $this->Number->currency($service->price) ?></td>
                            <td>
                                <?= $service->has('service_type') ? $this->Html->link(
                                    $service->service_type->name,
                                    ['controller' => 'ServiceTypes', 'action' => 'view', $service->service_type->id]
                                ) : '' ?>
                            </td>
                            <td><?= $service->not_for_new_customers ? __('Yes') : __('No'); ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Services', 'action' => 'view', $service->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Services', 'action' => 'edit', $service->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Services', 'action' => 'delete', $service->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $service->id)]
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
