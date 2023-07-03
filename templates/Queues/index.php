<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Queue> $queues
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

<div class="queues index content">
    <?= $this->AuthLink->link(__('New Queue'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Queues') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('caption') ?></th>
                    <th><?= $this->Paginator->sort('fup') ?></th>
                    <th><?= $this->Paginator->sort('limit') ?></th>
                    <th><?= $this->Paginator->sort('overlimit_fragment') ?></th>
                    <th><?= $this->Paginator->sort('overlimit_cost') ?></th>
                    <th><?= $this->Paginator->sort('speed_up') ?></th>
                    <th><?= $this->Paginator->sort('speed_down') ?></th>
                    <th><?= $this->Paginator->sort('cto_category') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queues as $queue) : ?>
                <tr>
                    <td><?= h($queue->name) ?></td>
                    <td><?= h($queue->caption) ?></td>
                    <td><?= $this->Number->format($queue->fup) ?></td>
                    <td><?= $this->Number->format($queue->limit) ?></td>
                    <td><?= $this->Number->format($queue->overlimit_fragment) ?></td>
                    <td><?= $this->Number->currency($queue->overlimit_cost) ?></td>
                    <td><?= $this->Number->format($queue->speed_up) ?></td>
                    <td><?= $this->Number->format($queue->speed_down) ?></td>
                    <td><?= h($queue->cto_category) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $queue->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $queue->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $queue->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $queue->id)]
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
