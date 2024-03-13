<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AccessCredential> $accessCredentials
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

<div class="accessCredentials index content">
    <?= $this->AuthLink->link(
        __('New Access Credential'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Access Credentials') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <?php if (!isset($customer_id)) : ?>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <?php endif; ?>
                    <?php if (!isset($contract_id)) : ?>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <?php endif; ?>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('ip_address', __('IP Address')) ?></th>
                    <th><?= $this->Paginator->sort('port') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accessCredentials as $accessCredential) : ?>
                <tr>
                    <?php if (!isset($customer_id)) : ?>
                    <td><?= $accessCredential->hasValue('customer') ?
                        $this->Html->link(
                            $accessCredential->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $accessCredential->customer->id]
                        ) : '' ?></td>
                    <td><?= $accessCredential->hasValue('customer') ?
                        h($accessCredential->customer->number) : '' ?></td>
                    <?php endif; ?>
                    <?php if (!isset($contract_id)) : ?>
                    <td><?= $accessCredential->hasValue('contract') ?
                        $this->Html->link(
                            $accessCredential->contract->name,
                            ['controller' => 'Contracts', 'action' => 'view', $accessCredential->contract->id]
                        ) : '' ?></td>
                    <?php endif; ?>
                    <td><?= h($accessCredential->name) ?></td>
                    <td><?= h($accessCredential->username) ?></td>
                    <td><?= h($accessCredential->ip_address) ?></td>
                    <td><?= $accessCredential->port === null ?
                        '' : $this->Number->format($accessCredential->port) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $accessCredential->id]
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $accessCredential->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $accessCredential->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $accessCredential->id)]
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
