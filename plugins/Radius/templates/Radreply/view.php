<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'Edit RADIUS Reply'),
                ['action' => 'edit', $radreply->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Delete RADIUS Reply'),
                ['action' => 'delete', $radreply->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radreply->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List RADIUS Replies'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'New RADIUS Reply'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radreply view content">
            <h3><?= h($radreply->id) ?></h3>
            <table>
                <tr>
                    <th><?= __d('radius', 'Username') ?></th>
                    <td><?= $radreply->has('account') ? $this->Html->link(
                        $radreply->account->username,
                        ['controller' => 'Accounts', 'action' => 'view', $radreply->account->id]
                    ) : $radreply->username ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Attribute') ?></th>
                    <td><?= h($radreply->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Op') ?></th>
                    <td><?= h($radreply->op) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Value') ?></th>
                    <td><?= h($radreply->value) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Id') ?></th>
                    <td><?= $this->Number->format($radreply->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
