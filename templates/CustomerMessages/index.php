<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CustomerMessage> $customerMessages
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

<div class="customerMessages index content">
    <?= $this->AuthLink->link(
        __('New Customer Message'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <?= $this->AuthLink->link(
        __('New Bulk Customer Message'),
        ['action' => 'add-bulk'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Customer Messages') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('direction') ?></th>
                    <th><?= $this->Paginator->sort('recipients') ?></th>
                    <th><?= $this->Paginator->sort('subject') ?></th>
                    <th><?= $this->Paginator->sort('body_format') ?></th>
                    <th><?= $this->Paginator->sort('delivery_status') ?></th>
                    <th><?= $this->Paginator->sort('processed') ?></th>
                    <th><?= $this->Paginator->sort('identifier') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customerMessages as $customerMessage) : ?>
                <tr>
                    <td><?= $customerMessage->hasValue('customer') ?
                        $this->AuthLink->link(
                            $customerMessage->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $customerMessage->customer->id]
                        ) : '' ?></td>
                    <td><?= $customerMessage->type === null ? '' : h($customerMessage->type->label()) ?></td>
                    <td><?= $customerMessage->direction === null ? '' : h($customerMessage->direction->label()) ?></td>
                    <td><?= implode('<br>', $customerMessage->recipients) ?></td>
                    <td><?= h($customerMessage->subject) ?></td>
                    <td><?= $customerMessage->body_format === null ?
                        '' : h($customerMessage->body_format->label()) ?></td>
                    <td><?= $customerMessage->delivery_status === null ?
                        '' : h($customerMessage->delivery_status->label()) ?></td>
                    <td><?= h($customerMessage->processed) ?></td>
                    <td><?= h($customerMessage->identifier) ?></td>
                    <td><?= h($customerMessage->created) ?></td>
                    <td><?= h($customerMessage->modified) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $customerMessage->id]
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $customerMessage->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $customerMessage->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $customerMessage->id)]
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
