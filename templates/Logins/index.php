<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Login> $logins
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="logins index content">
    <?= $this->AuthLink->link(__('New Login'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Logins') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('login') ?></th>
                    <th><?= $this->Paginator->sort('rights') ?></th>
                    <th><?= $this->Paginator->sort('locked') ?></th>
                    <th><?= $this->Paginator->sort('last_granted') ?></th>
                    <th><?= $this->Paginator->sort('last_granted_ip') ?></th>
                    <th><?= $this->Paginator->sort('last_denied') ?></th>
                    <th><?= $this->Paginator->sort('last_denied_ip') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logins as $login) : ?>
                <tr>
                    <td>
                        <?= $login->__isset('customer') ? $this->Html->link(
                            $login->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $login->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $login->__isset('customer') ? h($login->customer->number) : '' ?></td>
                    <td><?= h($login->login) ?></td>
                    <td><?= h($login->getRightsName()) ?></td>
                    <td><?= $login->locked ? __('Yes') : __('No'); ?></td>
                    <td><?= h($login->last_granted) ?></td>
                    <td><?= h($login->last_granted_ip) ?></td>
                    <td><?= h($login->last_denied) ?></td>
                    <td><?= h($login->last_denied_ip) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $login->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $login->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $login->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $login->id)]
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
