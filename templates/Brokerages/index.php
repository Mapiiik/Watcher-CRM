<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Brokerage[]|\Cake\Collection\CollectionInterface $brokerages
 */
?>
<div class="brokerages index content">
    <?= $this->Html->link(__('New Brokerage'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Brokerages') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brokerages as $brokerage): ?>
                <tr>
                    <td><?= $this->Number->format($brokerage->id) ?></td>
                    <td><?= h($brokerage->name) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $brokerage->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $brokerage->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $brokerage->id], ['confirm' => __('Are you sure you want to delete # {0}?', $brokerage->id)]) ?>
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
