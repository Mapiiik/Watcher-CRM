<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Radreply'),
                ['action' => 'edit', $radreply->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Radreply'),
                ['action' => 'delete', $radreply->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $radreply->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Radreply'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radreply'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radreply view content">
            <h3><?= h($radreply->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= $radreply->has('account') ? $this->Html->link(
                        $radreply->account->username,
                        ['controller' => 'Accounts', 'action' => 'view', $radreply->account->id]
                    ) : $radreply->username ?></td>
                </tr>
                <tr>
                    <th><?= __('Attribute') ?></th>
                    <td><?= h($radreply->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __('Op') ?></th>
                    <td><?= h($radreply->op) ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($radreply->value) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radreply->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
