<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 * @var string $title
 * @var \App\Model\Entity\Task $task
 * @var string[] $priorities
 */
use Cake\Routing\Router;

// set title
$this->assign('title', $title);

// temporarily remove query parameters in Router
$request = Router::getRequest();
Router::setRequest($request->withQueryParams([]));
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
<h4><?= h($task->subject) ?></h4>
<table>
    <tr>
        <td>
            <table>
                <tr>
                    <th><?= __('Task Type') ?></th>
                    <td><?= $task->has('task_type') ? h($task->task_type->name) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Priority') ?></th>
                    <td><?= $priorities[$task->priority] ?></td>
                </tr>
                <tr>
                    <th><?= __('Task State') ?></th>
                    <td><?= $task->has('task_state') ? h($task->task_state->name) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Dealer') ?></th>
                    <td><?= $task->has('dealer') ? h($task->dealer->name) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Subject') ?></th>
                    <td><?= h($task->subject) ?></td>
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
                    <th><?= __('Customer') ?></th>
                    <td><?= $task->has('customer') ? $this->Html->link(
                        $task->customer->name,
                        ['controller' => 'Customers', 'action' => 'view', $task->customer->id, '_full' => true]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Access Point') ?></th>
                    <td><?= $task->has('access_point') ? h($task->access_point['name']) : '' ?></td>
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
                    <td><?= $this->Number->format($task->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($task->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $task->has('creator') ? h($task->creator->username) : h($task->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($task->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $task->has('modifier') ? h($task->modifier->username) : h($task->modified_by) ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?= $this->Html->link(
    __('View Task'),
    ['controller' => 'Tasks', 'action' => 'view', $task->id, '_full' => true]
) ?>
<div class="text">
    <strong><?= __('Text') ?></strong>
    <blockquote>
        <?= $this->Text->autoParagraph(h($task->text)); ?>
    </blockquote>
</div>
<?php
// put query parameters back to Router
Router::setRequest($request);
?>
