<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RouterContact[]|\Cake\Collection\CollectionInterface $routerContacts
 */
?>
<div class="routerContacts index content">
    <?= $this->Html->link(__('New Router Contact'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Router Contacts') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('router_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routerContacts as $routerContact): ?>
                <tr>
                    <td><?= $routerContact->has('router') ? $this->Html->link($routerContact->router->name, ['controller' => 'Routers', 'action' => 'view', $routerContact->router->id]) : '' ?></td>
                    <td><?= $routerContact->has('customer') ? $this->Html->link($routerContact->customer->title, ['controller' => 'Customers', 'action' => 'view', $routerContact->customer->id]) : '' ?></td>
                    <td><?= $this->Number->format($routerContact->id) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $routerContact->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $routerContact->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $routerContact->id], ['confirm' => __('Are you sure you want to delete # {0}?', $routerContact->id)]) ?>
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
