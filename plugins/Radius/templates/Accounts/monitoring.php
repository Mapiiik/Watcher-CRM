<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Account $account
 * @var bool $details
 */

use Cake\I18n\FrozenTime;
?>

<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'Edit RADIUS Account'),
                ['action' => 'edit', $account->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Delete RADIUS Account'),
                ['action' => 'delete', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $account->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List RADIUS Accounts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'New RADIUS Account'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="accounts view content">
            <?= $this->Html->link(
                __d('radius', 'View RADIUS Account'),
                ['action' => 'view', $account->id],
                ['class' => 'button float-right']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'RADIUS Disconnect Request'),
                ['action' => 'disconnectRequest', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to disconnect {0}?', $account->username),
                    'class' => 'button float-right',
                ]
            ) ?>
            <h3><?= h($account->username) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Customer') ?></th>
                            <td><?= $account->has('customer') ? $this->Html->link(
                                $account->customer->name,
                                [
                                    'plugin' => null,
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $account->customer->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Contract') ?></th>
                            <td><?= $account->has('contract') ? $this->Html->link(
                                $account->contract->number,
                                [
                                    'plugin' => null,
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    'customer_id' => $account->contract->customer_id,
                                    $account->contract->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Username') ?></th>
                            <td><?= h($account->username) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Password') ?></th>
                            <td><?= h($account->password) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Type') ?></th>
                            <td><?= h($account->getType()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Active') ?></th>
                            <td><?= $account->active ? __d('radius', 'Yes') : __d('radius', 'No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Id') ?></th>
                            <td><?= $this->Number->format($account->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Created') ?></th>
                            <td><?= h($account->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Created By') ?></th>
                            <td><?= $account->has('creator') ? $this->Html->link(
                                $account->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $account->creator->id,
                                ]
                            ) : h($account->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Modified') ?></th>
                            <td><?= h($account->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Modified By') ?></th>
                            <td><?= $account->has('modifier') ? $this->Html->link(
                                $account->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $account->modifier->id,
                                ]
                            ) : h($account->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <div class="float-right">
                    <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
                    <?= $this->Form->control('show_details', [
                        'label' => __d('radius', 'Show Details'),
                        'type' => 'checkbox',
                        'onchange' => 'this.form.submit();',
                    ]) ?>
                    <?= $this->Form->end() ?>
                </div>
                <h4><?= __d('radius', 'Related RADIUS Accountings') ?></h4>
                <?php if (!empty($account->radacct)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <?php if ($details) : ?>
                            <th><?= __d('radius', 'Service Type') ?></th>
                            <th><?= __d('radius', 'Framed Protocol') ?></th>
                            <th><?= __d('radius', 'Called Station ID') ?></th>
                            <?php endif ?>
                            <th><?= __d('radius', 'Calling Station ID') ?></th>
                            <th><?= __d('radius', 'Framed IP Address') ?></th>
                            <th><?= __d('radius', 'Framed IPv6 Address') ?></th>
                            <th><?= __d('radius', 'Framed IPv6 Prefix') ?></th>
                            <th><?= __d('radius', 'Delegated IPv6 Prefix') ?></th>
                            <?php if ($details) : ?>
                            <th><?= __d('radius', 'Framed Interface ID') ?></th>
                            <?php endif ?>
                            <th><?= __d('radius', 'NAS IP Address') ?></th>
                            <th><?= __d('radius', 'NAS Port ID') ?></th>
                            <?php if ($details) : ?>
                            <th><?= __d('radius', 'NAS Port Type') ?></th>
                            <?php endif ?>
                            <th><?= __d('radius', 'Network Access Server') ?></th>
                            <th><?= __d('radius', 'Start Time') ?></th>
                            <?php if ($details) : ?>
                            <th><?= __d('radius', 'Update Time') ?></th>
                            <th><?= __d('radius', 'Update Interval') ?></th>
                            <?php endif ?>
                            <th><?= __d('radius', 'Stop Time') ?></th>
                            <?php if ($details) : ?>
                            <th><?= __d('radius', 'Termination Cause') ?></th>
                            <?php endif ?>
                            <th><?= __d('radius', 'Session Time') ?></th>
                            <th><?= __d('radius', 'Uploaded') ?></th>
                            <th><?= __d('radius', 'Downloaded') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radacct as $radacct) : ?>
                        <tr>
                            <?php if ($details) : ?>
                            <td><?= h($radacct->servicetype) ?></td>
                            <td><?= h($radacct->framedprotocol) ?></td>
                            <td><?= h($radacct->calledstationid) ?></td>
                            <?php endif ?>
                            <td><?= h($radacct->callingstationid) ?></td>
                            <td><?= h($radacct->framedipaddress) ?></td>
                            <td><?= h($radacct->framedipv6address) ?></td>
                            <td><?= h($radacct->framedipv6prefix) ?></td>
                            <td><?= h($radacct->delegatedipv6prefix) ?></td>
                            <?php if ($details) : ?>
                            <td><?= h($radacct->framedinterfaceid) ?></td>
                            <?php endif ?>
                            <td><?= h($radacct->nasipaddress) ?></td>
                            <td><?= h($radacct->nasportid) ?></td>
                            <?php if ($details) : ?>
                            <td><?= h($radacct->nasporttype) ?></td>
                            <?php endif ?>
                            <td><?php
                            if (isset($radacct->routeros_devices_for_nas)) {
                                $device = $radacct->routeros_devices_for_nas->first();
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
                            <td><?= h($radacct->acctstarttime) ?></td>
                            <?php if ($details) : ?>
                            <td><?= h($radacct->acctupdatetime) ?></td>
                            <td><?= $radacct->acctinterval ?
                                FrozenTime::createFromTimestamp($radacct->acctinterval)
                                    ->diffForHumans(FrozenTime::createFromTimestamp(0), true) : '' ?></td>
                            <?php endif ?>
                            <td><?= h($radacct->acctstoptime) ?></td>
                            <?php if ($details) : ?>
                            <td><?= h($radacct->acctterminatecause) ?></td>
                            <?php endif ?>
                            <td><?= FrozenTime::createFromTimestamp($radacct->acctsessiontime)
                                ->diffForHumans(FrozenTime::createFromTimestamp(0), true) ?></td>
                            <td><?= $this->Number->toReadableSize($radacct->acctinputoctets) ?></td>
                            <td><?= $this->Number->toReadableSize($radacct->acctoutputoctets) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radacct', 'action' => 'view', $radacct->radacctid]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radacct', 'action' => 'edit', $radacct->radacctid],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radacct', 'action' => 'delete', $radacct->radacctid],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radacct->radacctid
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
                <h4><?= __d('radius', 'Related RADIUS Post Authentications') ?></h4>
                <?php if (!empty($account->radpostauth)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Id') ?></th>
                            <th><?= __d('radius', 'Username') ?></th>
                            <th><?= __d('radius', 'Pass') ?></th>
                            <th><?= __d('radius', 'Reply') ?></th>
                            <th><?= __d('radius', 'Called Station ID') ?></th>
                            <th><?= __d('radius', 'Calling Station ID') ?></th>
                            <th><?= __d('radius', 'Authentication Date') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radpostauth as $radpostauth) : ?>
                        <tr>
                            <td><?= h($radpostauth->id) ?></td>
                            <td><?= h($radpostauth->username) ?></td>
                            <td><?= h($radpostauth->pass) ?></td>
                            <td><?= h($radpostauth->reply) ?></td>
                            <td><?= h($radpostauth->calledstationid) ?></td>
                            <td><?= h($radpostauth->callingstationid) ?></td>
                            <td><?= h($radpostauth->authdate) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radpostauth', 'action' => 'view', $radpostauth->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radpostauth', 'action' => 'edit', $radpostauth->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radpostauth', 'action' => 'delete', $radpostauth->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radpostauth->id
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
