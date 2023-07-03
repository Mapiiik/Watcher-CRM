<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\Radius\Model\Entity\Account> $accounts
 * @var bool $show_contracts
 */
?>
<?php if (is_object($accounts) && !$accounts->isEmpty()) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if ($show_contracts) : ?>
            <th><?= __d('radius', 'Contract') ?></th>
            <?php endif; ?>
            <th><?= __d('radius', 'Username') ?></th>
            <th><?= __d('radius', 'Password') ?></th>
            <th><?= __d('radius', 'Active') ?></th>
            <th><?= __d('radius', 'IP Addresses') ?></th>
            <th><?= __d('radius', 'RADIUS User Groups') ?></th>
            <th><?= __d('radius', 'RADIUS Replies') ?></th>
            <th><?= __d('radius', 'NAS IP Address') ?></th>
            <th><?= __d('radius', 'Network Access Server') ?></th>
            <th class="actions"><?= __d('radius', 'Actions') ?></th>
        </tr>
        <?php foreach ($accounts as $account) : ?>
        <tr style="<?= $account->style ?>">
            <?php if ($show_contracts) : ?>
            <td><?= $account->__isset('contract') ?
                $this->Html->link(
                    $account->contract->number,
                    ['plugin' => null, 'controller' => 'Contracts', 'action' => 'view', $account->contract->id]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= h($account->username) ?></td>
            <td><?= h($account->password) ?></td>
            <td><?= $account->active ? __d('radius', 'Yes') : __d('radius', 'No'); ?></td>
            <td><?php
            foreach ($account->radreply as $radreply) {
                if (in_array($radreply->attribute, ['Framed-IP-Address', 'Framed-IPv6-Address'])) {
                    echo h($radreply->value) . '<br />';
                }
            }
            ?></td>
            <td><?php
            foreach ($account->radusergroup as $radusergroup) {
                echo h($radusergroup->groupname) . '<br />';
            }
            ?></td>
            <td><?php
            foreach ($account->radreply as $radreply) {
                echo h($radreply->attribute)
                    . ' ' . h($radreply->op)
                    . ' ' . h($radreply->value)
                    . '<br />';
            }
            ?></td>
            <td><?= h($account->radacct[0]->nasipaddress ?? '') ?></td>
            <td><?php
            if (isset($account->radacct[0]->routeros_devices_for_nas)) {
                $device = $account->radacct[0]->routeros_devices_for_nas->first();
                echo isset($device['access_point']['id']) ?
                    __d('radius', 'Access Point') . ': ' . $this->Html->link(
                        $device['access_point']['name'],
                        env('WATCHER_NMS_URL') . '/access-points/view/' . $device['access_point']['id'],
                        ['target' => '_blank']
                    ) . '<br>' : '';
                echo isset($device['id']) ?
                    $this->Html->link(
                        $device['name'],
                        env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
                        ['target' => '_blank']
                    ) . '<br>' : '';
                unset($device);
            }
            ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __d('radius', 'View'),
                    [
                        'plugin' => 'Radius',
                        'controller' => 'Accounts',
                        'action' => 'view',
                        $account->id,
                    ]
                ) ?>
                <?= $this->AuthLink->link(
                    __d('radius', 'Edit'),
                    [
                        'plugin' => 'Radius',
                        'controller' => 'Accounts',
                        'action' => 'edit',
                        $account->id,
                    ],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __d('radius', 'Delete'),
                    [
                        'plugin' => 'Radius',
                        'controller' => 'Accounts',
                        'action' => 'delete',
                        $account->id,
                    ],
                    ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $account->username)]
                ) ?>
                <br />
                <?= $this->AuthLink->link(
                    __d('radius', 'Monitoring'),
                    [
                        'plugin' => 'Radius',
                        'controller' => 'Accounts',
                        'action' => 'monitoring',
                        $account->id,
                    ]
                ) ?>
                <?= $this->AuthLink->postLink(
                    __d('radius', 'Update'),
                    [
                        'plugin' => 'Radius',
                        'controller' => 'Accounts',
                        'action' => 'updateRelatedRecords',
                        $account->id,
                    ],
                    [
                        'confirm' => __d('radius', 'Are you sure you want to update related records?'),
                    ]
                ) ?>
                <?= $this->AuthLink->postLink(
                    __d('radius', 'Disconnect'),
                    [
                        'plugin' => 'Radius',
                        'controller' => 'Accounts',
                        'action' => 'disconnectRequest',
                        $account->id,
                    ],
                    [
                        'confirm' => __d('radius', 'Are you sure you want to disconnect {0}?', $account->username),
                    ]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
