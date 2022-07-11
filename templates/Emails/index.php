<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Email[]|\Cake\Collection\CollectionInterface $emails
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column-responsive">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="emails index content">
    <?= $this->AuthLink->link(__('New Email'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Emails') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('email') ?></th>
                    <th><?= $this->Paginator->sort('use_for_billing') ?></th>
                    <th><?= $this->Paginator->sort('use_for_outages') ?></th>
                    <th><?= $this->Paginator->sort('use_for_commercial') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emails as $email) : ?>
                <tr>
                    <td><?= $this->Number->format($email->id) ?></td>
                    <td>
                        <?= $email->has('customer') ? $this->Html->link(
                            $email->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $email->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($email->email) ?></td>
                    <td><?= $email->use_for_billing ? __('Yes') : __('No'); ?></td>
                    <td><?= $email->use_for_outages ? __('Yes') : __('No'); ?></td>
                    <td><?= $email->use_for_commercial ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $email->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $email->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $email->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $email->id)]
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
