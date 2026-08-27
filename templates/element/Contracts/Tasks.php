<?php
/**
 * The tasks filed under a record, as they are listed beside the record itself.
 *
 * The contract each task belongs to is worth a column where the tasks of several contracts stand
 * together; on the card of one contract it would say the same thing on every row.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Task> $tasks
 * @var bool|null $contract_column
 */
?>
<?php if (!empty($tasks)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('Number') ?></th>
            <th><?= __('Task Type') ?></th>
            <th><?= __('Task State') ?></th>
            <th><?= __('Subject') ?></th>
            <th><?= __('Text') ?></th>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('User') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($tasks as $task) : ?>
        <tr style="<?= $task->style ?>">
            <td><?= h($task->number) ?></td>
            <td><?= $task->task_type !== null ? h($task->task_type->name) : '' ?></td>
            <td><?= $task->task_state !== null ? h($task->task_state->name) : '' ?></td>
            <td><?= h($task->subject) ?></td>
            <td style="overflow-wrap: break-word; max-width: 600px;">
                <?= nl2br(h($task->text ?? '')) ?>
            </td>
            <?php if (!empty($contract_column)) : ?>
            <td><?=
                $task->contract !== null ? $this->Html->link(
                    $task->contract->name ?? '(' . $task->contract->id . ')',
                    [
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $task->contract->id,
                        'customer_id' => $task->contract->customer_id,
                    ],
                ) : '' ?>
            </td>
            <?php endif; ?>
            <td><?= $task->user !== null ? h($task->user->name) : '' ?>
                <?php if ($task->collaborator_names !== '') : ?>
                    <br><small title="<?= h(__('Collaborators')) ?>">
                        <?= h($task->collaborator_names) ?>
                    </small>
                <?php endif ?>
            </td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'Tasks', 'action' => 'view', $task->id],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'Tasks', 'action' => 'edit', $task->id],
                    ['class' => 'win-link'],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'Tasks', 'action' => 'delete', $task->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->number)],
                ) ?>
                <?= $this->element('common/copy_url', [
                    // win-link null keeps the routing filter from marking the copied link as one
                    // that belongs inside the popup window - this listing is often read in one
                    'url' => $this->Url->build(
                        ['controller' => 'Tasks', 'action' => 'view', $task->id, '?' => ['win-link' => null]],
                        ['fullBase' => true],
                    ),
                    // said shorter than on a page of its own: among three other actions
                    // there is no room to spell out what the click does
                    'label' => __('Link'),
                    'as_link' => true,
                ]) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
