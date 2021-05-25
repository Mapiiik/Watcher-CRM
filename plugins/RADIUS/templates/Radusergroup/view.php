<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radusergroup
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radusergroup'), ['action' => 'edit', $radusergroup->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radusergroup'), ['action' => 'delete', $radusergroup->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radusergroup->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radusergroup'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radusergroup'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radusergroup view content">
            <h3><?= h($radusergroup->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= $radusergroup->has('radcheck') ? $this->Html->link($radusergroup->radcheck->username, ['controller' => 'Radcheck', 'action' => 'view', $radusergroup->radcheck->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Groupname') ?></th>
                    <td><?= h($radusergroup->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __('Priority') ?></th>
                    <td><?= $this->Number->format($radusergroup->priority) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radusergroup->id) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Groupname') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radusergroup->groupname)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Radgroupcheck') ?></h4>
                <?php if (!empty($radusergroup->radgroupcheck)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Groupname') ?></th>
                            <th><?= __('Attribute') ?></th>
                            <th><?= __('Op') ?></th>
                            <th><?= __('Value') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radusergroup->radgroupcheck as $radgroupcheck) : ?>
                        <tr>
                            <td><?= h($radgroupcheck->id) ?></td>
                            <td><?= h($radgroupcheck->groupname) ?></td>
                            <td><?= h($radgroupcheck->attribute) ?></td>
                            <td><?= h($radgroupcheck->op) ?></td>
                            <td><?= h($radgroupcheck->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Radgroupcheck', 'action' => 'view', $radgroupcheck->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Radgroupcheck', 'action' => 'edit', $radgroupcheck->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Radgroupcheck', 'action' => 'delete', $radgroupcheck->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radgroupcheck->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Radgroupreply') ?></h4>
                <?php if (!empty($radusergroup->radgroupreply)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Groupname') ?></th>
                            <th><?= __('Attribute') ?></th>
                            <th><?= __('Op') ?></th>
                            <th><?= __('Value') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radusergroup->radgroupreply as $radgroupreply) : ?>
                        <tr>
                            <td><?= h($radgroupreply->id) ?></td>
                            <td><?= h($radgroupreply->groupname) ?></td>
                            <td><?= h($radgroupreply->attribute) ?></td>
                            <td><?= h($radgroupreply->op) ?></td>
                            <td><?= h($radgroupreply->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Radgroupreply', 'action' => 'view', $radgroupreply->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Radgroupreply', 'action' => 'edit', $radgroupreply->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Radgroupreply', 'action' => 'delete', $radgroupreply->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radgroupreply->id)]) ?>
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
