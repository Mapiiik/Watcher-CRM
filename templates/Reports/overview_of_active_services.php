<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Service[]|\Cake\Collection\CollectionInterface $services
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Reports'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="reports index content">
            <h3><?= __('Overview of active services') ?></h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= $this->Paginator->sort('id') ?></th>
                            <th><?= $this->Paginator->sort('name') ?></th>
                            <th><?= $this->Paginator->sort('price') ?></th>
                            <th><?= $this->Paginator->sort('service_type_id') ?></th>
                            <th><?= $this->Paginator->sort('queue_id') ?></th>
                            <th><?= __('Number of Uses') ?></th>
                            <th><?= __('Number of Uses (nonbusiness)') ?></th>
                            <th><?= __('Sum') ?></th>
                            <th><?= __('Fixed Discount Sum') ?></th>
                            <th><?= __('Percentage Discount Sum') ?></th>
                            <th><?= __('Total Sum') ?></th>
                            <th><?= __('Total Sum (nonbusiness)') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service) : ?>
                        <tr>
                            <td><?= $this->Number->format($service->id) ?></td>
                            <td><?= h($service->name) ?></td>
                            <td><?= $this->Number->currency($service->price) ?></td>
                            <td><?= $service->has('service_type') ? $this->Html->link($service->service_type->name, [
                                'controller' => 'ServiceTypes',
                                'action' => 'view',
                                $service->service_type->id,
                            ]) : '' ?></td>
                            <td><?= $service->has('queue') ? $this->Html->link($service->queue->name, [
                                'controller' => 'Queues',
                                'action' => 'view',
                                $service->queue->id,
                            ]) : '' ?></td>
                            <td><?= $this->Number->format($service->number_of_uses) ?></td>
                            <td><?= $this->Number->format($service->number_of_uses_nonbusiness) ?></td>
                            <td><?= $this->Number->currency($service->sum) ?></td>
                            <td><?= $this->Number->currency($service->fixed_discount_sum) ?></td>
                            <td><?= $this->Number->currency($service->percentage_discount_sum) ?></td>
                            <td><?= $this->Number->currency($service->total_sum) ?></td>
                            <td><?= $this->Number->currency($service->total_sum_nonbusiness) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(__('View'), [
                                    'controller' => 'Services',
                                    'action' => 'view',
                                    $service->id,
                                ]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="paginator">
                <ul class="pagination">
                    <?= $this->Paginator->first('<< ' . __('first')) ?>
                    <?= $this->Paginator->prev('< ' . __('previous')) ?>
                    <?= $this->Paginator->numbers() ?>
                    <?= $this->Paginator->next(__('next') . ' >') ?>
                    <?= $this->Paginator->last(__('last') . ' >>') ?>
                </ul>
                <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
            </div>
        </div>
    </div>
</div>
