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
    <div class="column-responsive column-90">
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
