<?php
use Cake\Routing\Router;

/**
 * @var \App\View\AppView $this
 * @var string $title
 * @var \App\Model\Entity\Task $task
 */

// set title
$this->assign('title', $title);

// temporarily remove query parameters in Router
$request = Router::getRequest();
if ($request !== null) {
    Router::setRequest($request->withQueryParams([]));
}
?>

<style>
table, td, th {
  border: 1px solid;
}

table {
  width: 100%;
  border-collapse: collapse;
}
</style>
<h2><?= $this->fetch('title') ?></h2>
<table>
    <tr>
        <td>
            <table>
                <tr>
                    <th><?= __('Task Type') ?></th>
                    <td><?= $task->task_type !== null ? h($task->task_type->name) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Priority') ?></th>
                    <td><?= h($task->getPriorityName()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Task State') ?></th>
                    <td><?= $task->task_state !== null ? h($task->task_state->name) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $task->user !== null ? h($task->user->name) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Email') ?></th>
                    <td><?= h($task->email) ?></td>
                </tr>
                <tr>
                    <th><?= __('Phone') ?></th>
                    <td><?= h($task->phone) ?></td>
                </tr>
                <tr>
                    <th><?= __('Access Point') ?></th>
                    <td><?= $this->element('AccessPoints/link', [
                        'id' => $task->access_point_id,
                        // read away from the page and long after it was sent, so the name is
                        // taken as it came and a system that was down is nobody's business here
                        'name' => $task->access_point->data?->name,
                    ]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $task->customer !== null ? $this->Html->link(
                        $task->customer->name ?? '(' . $task->customer->id . ')',
                        [
                            'controller' => 'Customers',
                            'action' => 'view',
                            $task->customer->id,
                            '_full' => true,
                        ],
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $task->contract !== null ? $this->Html->link(
                        $task->contract->name ?? '(' . $task->contract->id . ')',
                        [
                            'controller' => 'Contracts',
                            'action' => 'view',
                            $task->contract->id,
                            'customer_id' => $task->contract->customer_id,
                            '_full' => true,
                        ],
                    ) : '' ?></td>
                </tr>
            </table>
        </td>
        <td>
            <table>
                <tr>
                    <th><?= __('Start Date') ?></th>
                    <td><?= h($task->start_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Estimated Date') ?></th>
                    <td><?= h($task->estimated_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Critical Date') ?></th>
                    <td><?= h($task->critical_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Finish Date') ?></th>
                    <td><?= h($task->finish_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($task->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($task->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $task->creator !== null ? h($task->creator->username) : h($task->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($task->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $task->modifier !== null ? h($task->modifier->username) : h($task->modified_by) ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?= $this->Html->link(
    __('View Task'),
    ['controller' => 'Tasks', 'action' => 'view', $task->id, '_full' => true],
) ?>
<div class="text">
    <strong><?= __('Subject') ?></strong>
    <h4><?= h($task->subject) ?></h4>
</div>
<div class="text">
    <strong><?= __('Summary Text') ?></strong>
    <blockquote>
        <?= h($task->summary_text) ?>
    </blockquote>
</div>
<div class="text">
    <strong><?= __('Text') ?></strong>
    <blockquote style="overflow-wrap: break-word;">
        <?= $this->Text->autoParagraph(h($task->text)); ?>
    </blockquote>
</div>
<?php
// put query parameters back to Router
if ($request !== null) {
    Router::setRequest($request);
}
?>
