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
            <?= $this->Html->link(__('Edit Service'), ['action' => 'edit', $service->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Service'), ['action' => 'delete', $service->id], ['confirm' => __('Are you sure you want to delete # {0}?', $service->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Services'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Service'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
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
                    <td><?= $service->has('service_type') ? $this->Html->link($service->service_type->name, ['controller' => 'ServiceTypes', 'action' => 'view', $service->service_type->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Queue') ?></th>
                    <td><?= $service->has('queue') ? $this->Html->link($service->queue->name, ['controller' => 'Queues', 'action' => 'view', $service->queue->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($service->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Price') ?></th>
                    <td><?= $this->Number->format($service->price) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($service->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($service->modified) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Billings') ?></h4>
                <?php if (!empty($service->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Separate') ?></th>
                            <th><?= __('Service Id') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($service->billings as $billings) : ?>
                        <tr>
                            <td><?= h($billings->id) ?></td>
                            <td><?= h($billings->customer_id) ?></td>
                            <td><?= h($billings->text) ?></td>
                            <td><?= h($billings->price) ?></td>
                            <td><?= h($billings->billing_from) ?></td>
                            <td><?= h($billings->note) ?></td>
                            <td><?= h($billings->active) ?></td>
                            <td><?= h($billings->modified_by) ?></td>
                            <td><?= h($billings->modified) ?></td>
                            <td><?= h($billings->created_by) ?></td>
                            <td><?= h($billings->created) ?></td>
                            <td><?= h($billings->billing_until) ?></td>
                            <td><?= h($billings->separate) ?></td>
                            <td><?= h($billings->service_id) ?></td>
                            <td><?= h($billings->quantity) ?></td>
                            <td><?= h($billings->contract_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Billings', 'action' => 'view', $billings->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Billings', 'action' => 'edit', $billings->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Billings', 'action' => 'delete', $billings->id], ['confirm' => __('Are you sure you want to delete # {0}?', $billings->id)]) ?>
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
