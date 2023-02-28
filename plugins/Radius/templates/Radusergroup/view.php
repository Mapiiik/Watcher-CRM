<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radusergroup
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'Edit RADIUS User Group'),
                ['action' => 'edit', $radusergroup->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Delete RADIUS User Group'),
                ['action' => 'delete', $radusergroup->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radusergroup->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List RADIUS User Groups'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'New RADIUS User Group'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radusergroup view content">
            <h3><?= h($radusergroup->id) ?></h3>
            <table>
                <tr>
                    <th><?= __d('radius', 'Username') ?></th>
                    <td><?= $radusergroup->has('account') ? $this->Html->link(
                        $radusergroup->account->username,
                        ['controller' => 'Accounts', 'action' => 'view', $radusergroup->account->id]
                    ) : $radusergroup->username ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Groupname') ?></th>
                    <td><?= h($radusergroup->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Priority') ?></th>
                    <td><?= $this->Number->format($radusergroup->priority) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Id') ?></th>
                    <td><?= $this->Number->format($radusergroup->id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __d('radius', 'Related RADIUS Group Checks') ?></h4>
                <?php if (!empty($radusergroup->radgroupcheck)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Groupname') ?></th>
                            <th><?= __d('radius', 'Attribute') ?></th>
                            <th><?= __d('radius', 'Op') ?></th>
                            <th><?= __d('radius', 'Value') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($radusergroup->radgroupcheck as $radgroupcheck) : ?>
                        <tr>
                            <td><?= h($radgroupcheck->groupname) ?></td>
                            <td><?= h($radgroupcheck->attribute) ?></td>
                            <td><?= h($radgroupcheck->op) ?></td>
                            <td><?= h($radgroupcheck->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radgroupcheck', 'action' => 'view', $radgroupcheck->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radgroupcheck', 'action' => 'edit', $radgroupcheck->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radgroupcheck', 'action' => 'delete', $radgroupcheck->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radgroupcheck->id
                                    )]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __d('radius', 'Related RADIUS Group Replies') ?></h4>
                <?php if (!empty($radusergroup->radgroupreply)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Groupname') ?></th>
                            <th><?= __d('radius', 'Attribute') ?></th>
                            <th><?= __d('radius', 'Op') ?></th>
                            <th><?= __d('radius', 'Value') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($radusergroup->radgroupreply as $radgroupreply) : ?>
                        <tr>
                            <td><?= h($radgroupreply->groupname) ?></td>
                            <td><?= h($radgroupreply->attribute) ?></td>
                            <td><?= h($radgroupreply->op) ?></td>
                            <td><?= h($radgroupreply->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radgroupreply', 'action' => 'view', $radgroupreply->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radgroupreply', 'action' => 'edit', $radgroupreply->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radgroupreply', 'action' => 'delete', $radgroupreply->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radgroupreply->id
                                    )]
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
