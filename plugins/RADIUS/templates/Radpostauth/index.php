<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $radpostauths
 */
?>
<div class="radpostauth index content">
    <?= $this->Html->link(__('New Radpostauth'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Radpostauth') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('pass') ?></th>
                    <th><?= $this->Paginator->sort('reply') ?></th>
                    <th><?= $this->Paginator->sort('calledstationid') ?></th>
                    <th><?= $this->Paginator->sort('callingstationid') ?></th>
                    <th><?= $this->Paginator->sort('authdate') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radpostauths as $radpostauth) : ?>
                <tr>
                    <td><?= $this->Number->format($radpostauth->id) ?></td>
                    <td><?= h($radpostauth->username) ?></td>
                    <td><?= h($radpostauth->pass) ?></td>
                    <td><?= h($radpostauth->reply) ?></td>
                    <td><?= h($radpostauth->calledstationid) ?></td>
                    <td><?= h($radpostauth->callingstationid) ?></td>
                    <td><?= h($radpostauth->authdate) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $radpostauth->id]) ?>
                        <?= $this->Html->link(
                            __('Edit'),
                            ['action' => 'edit', $radpostauth->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $radpostauth->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $radpostauth->id)]
                        ) ?>
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
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
