<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Login[]|\Cake\Collection\CollectionInterface $logins
 */
?>
<div class="logins index content">
    <?= $this->Html->link(__('New Login'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Logins') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('login') ?></th>
                    <th><?= $this->Paginator->sort('password') ?></th>
                    <th><?= $this->Paginator->sort('rights') ?></th>
                    <th><?= $this->Paginator->sort('locked') ?></th>
                    <th><?= $this->Paginator->sort('last_granted') ?></th>
                    <th><?= $this->Paginator->sort('last_granted_ip') ?></th>
                    <th><?= $this->Paginator->sort('last_denied') ?></th>
                    <th><?= $this->Paginator->sort('last_denied_ip') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logins as $login): ?>
                <tr>
                    <td><?= $this->Number->format($login->id) ?></td>
                    <td><?= $login->has('customer') ? $this->Html->link($login->customer->name, ['controller' => 'Customers', 'action' => 'view', $login->customer->id]) : '' ?></td>
                    <td><?= h($login->login) ?></td>
                    <td><?= h($login->password) ?></td>
                    <td><?= $this->Number->format($login->rights) ?></td>
                    <td><?= $this->Number->format($login->locked) ?></td>
                    <td><?= h($login->last_granted) ?></td>
                    <td><?= h($login->last_granted_ip) ?></td>
                    <td><?= h($login->last_denied) ?></td>
                    <td><?= h($login->last_denied_ip) ?></td>
                    <td><?= $this->Number->format($login->modified_by) ?></td>
                    <td><?= h($login->modified) ?></td>
                    <td><?= $this->Number->format($login->created_by) ?></td>
                    <td><?= h($login->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $login->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $login->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $login->id], ['confirm' => __('Are you sure you want to delete # {0}?', $login->id)]) ?>
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
