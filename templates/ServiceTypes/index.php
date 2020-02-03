<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceType[]|\Cake\Collection\CollectionInterface $serviceTypes
 */
?>
<div class="serviceTypes index content">
    <?= $this->Html->link(__('New Service Type'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Service Types') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($serviceTypes as $serviceType): ?>
                <tr>
                    <td><?= $this->Number->format($serviceType->id) ?></td>
                    <td><?= h($serviceType->created) ?></td>
                    <td><?= h($serviceType->modified) ?></td>
                    <td><?= h($serviceType->name) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $serviceType->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $serviceType->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $serviceType->id], ['confirm' => __('Are you sure you want to delete # {0}?', $serviceType->id)]) ?>
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
