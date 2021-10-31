<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EquipmentType[]|\Cake\Collection\CollectionInterface $equipmentTypes
 */
?>
<div class="equipmentTypes index content">
    <?= $this->AuthLink->link(__('New Equipment Type'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Equipment Types') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('price') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipmentTypes as $equipmentType): ?>
                <tr>
                    <td><?= $this->Number->format($equipmentType->id) ?></td>
                    <td><?= h($equipmentType->name) ?></td>
                    <td><?= $this->Number->format($equipmentType->price) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $equipmentType->id]) ?>
                        <?= $this->AuthLink->link(__('Edit'), ['action' => 'edit', $equipmentType->id]) ?>
                        <?= $this->AuthLink->postLink(__('Delete'), ['action' => 'delete', $equipmentType->id], ['confirm' => __('Are you sure you want to delete # {0}?', $equipmentType->id)]) ?>
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
