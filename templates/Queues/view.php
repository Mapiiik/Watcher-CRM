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
            <?= $this->Html->link(__('Edit Queue'), ['action' => 'edit', $queue->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Queue'), ['action' => 'delete', $queue->id], ['confirm' => __('Are you sure you want to delete # {0}?', $queue->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Queues'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Queue'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="queues view content">
            <h3><?= h($queue->name) ?></h3>
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
                    <th><?= __('Service Type') ?></th>
                    <td><?= $queue->has('service_type') ? $this->Html->link($queue->service_type->name, ['controller' => 'ServiceTypes', 'action' => 'view', $queue->service_type->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($queue->id) ?></td>
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
                    <td><?= $this->Number->format($queue->overlimit_cost) ?></td>
                </tr>
                <tr>
                    <th><?= __('Speed Up') ?></th>
                    <td><?= $this->Number->format($queue->speed_up) ?></td>
                </tr>
                <tr>
                    <th><?= __('Speed Down') ?></th>
                    <td><?= $this->Number->format($queue->speed_down) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Ips') ?></h4>
                <?php if (!empty($queue->ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($queue->ips as $ips) : ?>
                        <tr>
                            <td><?= h($ips->ip) ?></td>
                            <td><?= h($ips->customer_id) ?></td>
                            <td><?= h($ips->note) ?></td>
                            <td><?= h($ips->contract_id) ?></td>
                            <td><?= h($ips->id) ?></td>
                            <td><?= h($ips->created) ?></td>
                            <td><?= h($ips->created_by) ?></td>
                            <td><?= h($ips->modified) ?></td>
                            <td><?= h($ips->modified_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Ips', 'action' => 'view', $ips->ip]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Ips', 'action' => 'edit', $ips->ip]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Ips', 'action' => 'delete', $ips->ip], ['confirm' => __('Are you sure you want to delete # {0}?', $ips->ip)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Removed Ips') ?></h4>
                <?php if (!empty($queue->removed_ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Removed By') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($queue->removed_ips as $removedIps) : ?>
                        <tr>
                            <td><?= h($removedIps->id) ?></td>
                            <td><?= h($removedIps->removed_by) ?></td>
                            <td><?= h($removedIps->removed) ?></td>
                            <td><?= h($removedIps->ip) ?></td>
                            <td><?= h($removedIps->customer_id) ?></td>
                            <td><?= h($removedIps->note) ?></td>
                            <td><?= h($removedIps->contract_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'RemovedIps', 'action' => 'view', $removedIps->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'RemovedIps', 'action' => 'edit', $removedIps->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'RemovedIps', 'action' => 'delete', $removedIps->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIps->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Services') ?></h4>
                <?php if (!empty($queue->services)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Service Type Id') ?></th>
                            <th><?= __('Queue Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($queue->services as $services) : ?>
                        <tr>
                            <td><?= h($services->id) ?></td>
                            <td><?= h($services->created) ?></td>
                            <td><?= h($services->modified) ?></td>
                            <td><?= h($services->name) ?></td>
                            <td><?= h($services->price) ?></td>
                            <td><?= h($services->service_type_id) ?></td>
                            <td><?= h($services->queue_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Services', 'action' => 'view', $services->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Services', 'action' => 'edit', $services->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Services', 'action' => 'delete', $services->id], ['confirm' => __('Are you sure you want to delete # {0}?', $services->id)]) ?>
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
