<?php
use Cake\I18n\DateTime;

/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Account $account
 * @var \Cake\Datasource\Paging\PaginatedResultSet<\Radius\Model\Entity\Radacct> $radaccts
 * @var \Cake\Datasource\Paging\PaginatedResultSet<\Radius\Model\Entity\Radpostauth> $radpostauths
 * @var bool $details
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'Edit RADIUS Account'),
                ['action' => 'edit', $account->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Account'),
                ['action' => 'delete', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $account->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Accounts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'New RADIUS Account'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="accounts view content">
            <?= $this->AuthLink->link(
                __d('radius', 'View RADIUS Account'),
                ['action' => 'view', $account->id],
                ['class' => 'button float-right']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('radius', 'RADIUS Disconnect Request'),
                ['action' => 'disconnectRequest', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to disconnect {0}?', $account->username),
                    'class' => 'button float-right',
                ]
            ) ?>
            <h3><?= h($account->username) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Customer') ?></th>
                            <td><?= $account->__isset('customer') ? $this->Html->link(
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
                            <th><?= __d('radius', 'Customer Number') ?></th>
                            <td><?= $account->__isset('customer') ? h($account->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Contract') ?></th>
                            <td><?= $account->__isset('contract') ? $this->Html->link(
                                $account->contract->number ?? '--',
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
                            <td><?= h($account->getTypeName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Active') ?></th>
                            <td><?= $account->active ? __d('radius', 'Yes') : __d('radius', 'No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
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
                            <td><?= $account->__isset('creator') ? $this->Html->link(
                                $account->creator->username,
                                [
                                    'controller' => 'AppUsers',
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
                            <td><?= $account->__isset('modifier') ? $this->Html->link(
                                $account->modifier->username,
                                [
                                    'controller' => 'AppUsers',
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
                <?php if (!empty($radaccts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <?php debug($radaccts); $this->Paginator->setPaginated($radaccts); ?>
                            <?php if ($details) : ?>
                            <th><?=
                                $this->Paginator->sort('servicetype', __d('radius', 'Service Type'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('framedprotocol', __d('radius', 'Framed Protocol'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('calledstationid', __d('radius', 'Called Station ID'))
                            ?></th>
                            <?php endif ?>
                            <th><?=
                                $this->Paginator->sort('callingstationid', __d('radius', 'Calling Station ID'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('framedipaddress', __d('radius', 'Framed IP Address'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('framedipv6address', __d('radius', 'Framed IPv6 Address'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('framedipv6prefix', __d('radius', 'Framed IPv6 Prefix'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('delegatedipv6prefix', __d('radius', 'Delegated IPv6 Prefix'))
                            ?></th>
                            <?php if ($details) : ?>
                            <th><?=
                                $this->Paginator->sort('framedinterfaceid', __d('radius', 'Framed Interface ID'))
                            ?></th>
                            <?php endif ?>
                            <th><?=
                                $this->Paginator->sort('nasipaddress', __d('radius', 'NAS IP Address'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('nasportid', __d('radius', 'NAS Port ID'))
                            ?></th>
                            <?php if ($details) : ?>
                            <th><?=
                                $this->Paginator->sort('nasporttype', __d('radius', 'NAS Port Type'))
                            ?></th>
                            <?php endif ?>
                            <th><?=
                                $this->Paginator->sort('nasipaddress', __d('radius', 'Network Access Server'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('acctstarttime', __d('radius', 'Start Time'))
                            ?></th>
                            <?php if ($details) : ?>
                            <th><?=
                                $this->Paginator->sort('acctupdatetime', __d('radius', 'Update Time'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('acctinterval', __d('radius', 'Update Interval'))
                            ?></th>
                            <?php endif ?>
                            <th><?=
                                $this->Paginator->sort('acctstoptime', __d('radius', 'Stop Time'))
                            ?></th>
                            <?php if ($details) : ?>
                            <th><?=
                                $this->Paginator->sort('acctterminatecause', __d('radius', 'Termination Cause'))
                            ?></th>
                            <?php endif ?>
                            <th><?=
                                $this->Paginator->sort('acctsessiontime', __d('radius', 'Session Time'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('acctinputoctets', __d('radius', 'Uploaded'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('acctoutputoctets', __d('radius', 'Downloaded'))
                            ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($radaccts as $radacct) : ?>
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
                                DateTime::createFromTimestamp($radacct->acctinterval)
                                    ->diffForHumans(DateTime::createFromTimestamp(0), true) : '' ?></td>
                            <?php endif ?>
                            <td><?= h($radacct->acctstoptime) ?></td>
                            <?php if ($details) : ?>
                            <td><?= h($radacct->acctterminatecause) ?></td>
                            <?php endif ?>
                            <td><?= $radacct->acctsessiontime === null ?
                                '' :
                                DateTime::createFromTimestamp($radacct->acctsessiontime)
                                    ->diffForHumans(DateTime::createFromTimestamp(0), true) ?></td>
                            <td><?= $radacct->acctinputoctets === null ?
                                '' : $this->Number->toReadableSize($radacct->acctinputoctets) ?></td>
                            <td><?= $radacct->acctoutputoctets === null ?
                                '' : $this->Number->toReadableSize($radacct->acctoutputoctets) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radacct', 'action' => 'view', $radacct->radacctid]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radacct', 'action' => 'edit', $radacct->radacctid],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
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
                <div class="paginator">
                    <ul class="pagination">
                        <?= $this->Paginator->first('<< ' . __d('radius', 'first')) ?>
                        <?= $this->Paginator->prev('< ' . __d('radius', 'previous')) ?>
                        <?= $this->Paginator->numbers() ?>
                        <?= $this->Paginator->next(__d('radius', 'next') . ' >') ?>
                        <?= $this->Paginator->last(__d('radius', 'last') . ' >>') ?>
                    </ul>
                    <p><?=
                        $this->Paginator->counter(
                            __d(
                                'radius',
                                'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'
                            )
                        ) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __d('radius', 'Related RADIUS Post Authentications') ?></h4>
                <?php if (!empty($radpostauths)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <?php $this->Paginator->setPaginated($radpostauths); ?>
                            <th><?=
                                $this->Paginator->sort('id', __d('radius', 'Id'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('username', __d('radius', 'Username'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('pass', __d('radius', 'Pass'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('reply', __d('radius', 'Reply'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('calledstationid', __d('radius', 'Called Station ID'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('callingstationid', __d('radius', 'Calling Station ID'))
                            ?></th>
                            <th><?=
                                $this->Paginator->sort('authdate', __d('radius', 'Authentication Date'))
                            ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($radpostauths as $radpostauth) : ?>
                        <tr>
                            <td><?= h($radpostauth->id) ?></td>
                            <td><?= h($radpostauth->username) ?></td>
                            <td><?= h($radpostauth->pass) ?></td>
                            <td><?= h($radpostauth->reply) ?></td>
                            <td><?= h($radpostauth->calledstationid) ?></td>
                            <td><?= h($radpostauth->callingstationid) ?></td>
                            <td><?= h($radpostauth->authdate) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radpostauth', 'action' => 'view', $radpostauth->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radpostauth', 'action' => 'edit', $radpostauth->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
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
                <div class="paginator">
                    <ul class="pagination">
                        <?= $this->Paginator->first('<< ' . __d('radius', 'first')) ?>
                        <?= $this->Paginator->prev('< ' . __d('radius', 'previous')) ?>
                        <?= $this->Paginator->numbers() ?>
                        <?= $this->Paginator->next(__d('radius', 'next') . ' >') ?>
                        <?= $this->Paginator->last(__d('radius', 'last') . ' >>') ?>
                    </ul>
                    <p><?=
                        $this->Paginator->counter(
                            __d(
                                'radius',
                                'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'
                            )
                        ) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
