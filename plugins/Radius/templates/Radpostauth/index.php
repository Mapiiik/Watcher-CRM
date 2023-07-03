<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\Cake\Datasource\EntityInterface> $radpostauths
 */
?>
<div class="radpostauth index content">
    <?= $this->AuthLink->link(
        __d('radius', 'New RADIUS Post Authentication'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'RADIUS Post Authentications') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('pass') ?></th>
                    <th><?= $this->Paginator->sort('reply') ?></th>
                    <th><?= $this->Paginator->sort('calledstationid') ?></th>
                    <th><?= $this->Paginator->sort('callingstationid') ?></th>
                    <th><?= $this->Paginator->sort('authdate') ?></th>
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radpostauths as $radpostauth) : ?>
                <tr>
                    <td><?= h($radpostauth->username) ?></td>
                    <td><?= h($radpostauth->pass) ?></td>
                    <td><?= h($radpostauth->reply) ?></td>
                    <td><?= h($radpostauth->calledstationid) ?></td>
                    <td><?= h($radpostauth->callingstationid) ?></td>
                    <td><?= h($radpostauth->authdate) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__d('radius', 'View'), ['action' => 'view', $radpostauth->id]) ?>
                        <?= $this->AuthLink->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $radpostauth->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $radpostauth->id],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radpostauth->id)]
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
