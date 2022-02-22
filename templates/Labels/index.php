<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Label[]|\Cake\Collection\CollectionInterface $labels
 */
?>
<div class="labels index content">
    <?= $this->AuthLink->link(__('New Label'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Labels') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('caption') ?></th>
                    <th><?= $this->Paginator->sort('color') ?></th>
                    <th><?= $this->Paginator->sort('validity') ?></th>
                    <th><?= $this->Paginator->sort('dynamic') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($labels as $label) : ?>
                <tr>
                    <td><?= $this->Number->format($label->id) ?></td>
                    <td><?= h($label->name) ?></td>
                    <td><?= h($label->caption) ?></td>
                    <td><?= h($label->color) ?></td>
                    <td><?= $this->Number->format($label->validity) ?></td>
                    <td><?= h($label->dynamic) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $label->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $label->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $label->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $label->id)]
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
