<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radgroupreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Radgroupreply'),
                ['action' => 'edit', $radgroupreply->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Radgroupreply'),
                ['action' => 'delete', $radgroupreply->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $radgroupreply->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(__('List Radgroupreply'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radgroupreply'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radgroupreply view content">
            <h3><?= h($radgroupreply->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Groupname') ?></th>
                    <td><?= h($radgroupreply->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __('Attribute') ?></th>
                    <td><?= h($radgroupreply->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __('Op') ?></th>
                    <td><?= h($radgroupreply->op) ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($radgroupreply->value) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radgroupreply->id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Radusergroup') ?></h4>
                <?php if (!empty($radgroupreply->radusergroup)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Username') ?></th>
                            <th><?= __('Groupname') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radgroupreply->radusergroup as $radusergroup) : ?>
                        <tr>
                            <td><?= h($radusergroup->id) ?></td>
                            <td><?= h($radusergroup->username) ?></td>
                            <td><?= h($radusergroup->groupname) ?></td>
                            <td><?= h($radusergroup->priority) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['controller' => 'Radusergroup', 'action' => 'view', $radusergroup->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['controller' => 'Radusergroup', 'action' => 'edit', $radusergroup->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Radusergroup', 'action' => 'delete', $radusergroup->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $radusergroup->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
