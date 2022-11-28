<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractState> $contractStates
 */
?>
<div class="contractStates index content">
    <?= $this->Html->link(__('New Contract State'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Contract States') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('color') ?></th>
                    <th><?= $this->Paginator->sort('active') ?></th>
                    <th><?= $this->Paginator->sort('billed') ?></th>
                    <th><?= $this->Paginator->sort('blocked') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contractStates as $contractState) : ?>
                <tr>
                    <td><?= h($contractState->name) ?></td>
                    <td style="background-color: <?= h($contractState->color) ?>;"><?= h($contractState->color) ?></td>
                    <td><?= $contractState->active ? __('Yes') : __('No'); ?></td>
                    <td><?= $contractState->billed ? __('Yes') : __('No'); ?></td>
                    <td><?= $contractState->blocked ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->Html->link(
                            __('View'),
                            ['action' => 'view', $contractState->id]
                        ) ?>
                        <?= $this->Html->link(
                            __('Edit'),
                            ['action' => 'edit', $contractState->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $contractState->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $contractState->id)]
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
