<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $nases
 */
?>
<div class="Nas index content">
    <?= $this->Html->link(__d('radius', 'New Nas'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __d('radius', 'Nas') ?></h3>
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
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nases as $nas) : ?>
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
                        <?= $this->Html->link(__d('radius', 'View'), ['action' => 'view', $nas->id]) ?>
                        <?= $this->Html->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $nas->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $nas->id],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $nas->id)]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __d('radius', 'first')) ?>
            <?= $this->Paginator->prev('< ' . __d('radius', 'previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__d('radius', 'next') . ' >') ?>
            <?= $this->Paginator->last(__d('radius', 'last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __d('radius', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
