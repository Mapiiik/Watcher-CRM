<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Radgroupreply $radgroupreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'Edit RADIUS Group Reply'),
                ['action' => 'edit', $radgroupreply->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Group Reply'),
                ['action' => 'delete', $radgroupreply->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radgroupreply->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Group Replies'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'New RADIUS Group Reply'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radgroupreply view content">
            <h3><?= h($radgroupreply->id) ?></h3>
            <table>
                <tr>
                    <th><?= __d('radius', 'Groupname') ?></th>
                    <td><?= h($radgroupreply->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Attribute') ?></th>
                    <td><?= h($radgroupreply->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Op') ?></th>
                    <td><?= h($radgroupreply->op) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Value') ?></th>
                    <td><?= h($radgroupreply->value) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Id') ?></th>
                    <td><?= $this->Number->format($radgroupreply->id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __d('radius', 'Related RADIUS User Groups') ?></h4>
                <?php if (!empty($radgroupreply->radusergroup)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Username') ?></th>
                            <th><?= __d('radius', 'Groupname') ?></th>
                            <th><?= __d('radius', 'Priority') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($radgroupreply->radusergroup as $radusergroup) : ?>
                        <tr>
                            <td><?= h($radusergroup->username) ?></td>
                            <td><?= h($radusergroup->groupname) ?></td>
                            <td><?= h($radusergroup->priority) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radusergroup', 'action' => 'view', $radusergroup->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radusergroup', 'action' => 'edit', $radusergroup->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radusergroup', 'action' => 'delete', $radusergroup->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radusergroup->id
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
