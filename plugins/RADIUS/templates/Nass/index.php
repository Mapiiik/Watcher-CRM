<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $nass
 */
?>
<div class="nass index content">
    <?= $this->Html->link(__('New Nas'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Nass') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('nasname') ?></th>
                    <th><?= $this->Paginator->sort('shortname') ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('ports') ?></th>
                    <th><?= $this->Paginator->sort('secret') ?></th>
                    <th><?= $this->Paginator->sort('server') ?></th>
                    <th><?= $this->Paginator->sort('community') ?></th>
                    <th><?= $this->Paginator->sort('description') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nass as $nas): ?>
                <tr>
                    <td><?= $this->Number->format($nas->id) ?></td>
                    <td><?= h($nas->nasname) ?></td>
                    <td><?= h($nas->shortname) ?></td>
                    <td><?= h($nas->type) ?></td>
                    <td><?= $this->Number->format($nas->ports) ?></td>
                    <td><?= h($nas->secret) ?></td>
                    <td><?= h($nas->server) ?></td>
                    <td><?= h($nas->community) ?></td>
                    <td><?= h($nas->description) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $nas->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $nas->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $nas->id], ['confirm' => __('Are you sure you want to delete # {0}?', $nas->id)]) ?>
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
