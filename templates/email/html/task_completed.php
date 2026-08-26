<?php
/**
 * Tells the report addresses that a task has been closed.
 *
 * Whoever reads this did not close the task and may never have seen it open, so who finished it
 * and when is said up front - the rest of the detail is the same as in any other task email.
 *
 * @var \App\View\AppView $this
 * @var string $title
 * @var \App\Model\Entity\Task $task
 */

// set title
$this->assign('title', $title);
?>
<h2><?= $this->fetch('title') ?></h2>
<table>
    <tr>
        <th><?= __('Task State') ?></th>
        <td><?= $task->task_state !== null ? h($task->task_state->name) : '' ?></td>
    </tr>
    <tr>
        <th><?= __('Closed By') ?></th>
        <td><?= $task->modifier !== null ? h($task->modifier->username) : h($task->modified_by) ?></td>
    </tr>
    <tr>
        <th><?= __('Closed') ?></th>
        <td><?= h($task->modified) ?></td>
    </tr>
</table>
<?= $this->element('Tasks/email_detail', ['task' => $task]) ?>
