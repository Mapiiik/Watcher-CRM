<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $account
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'Edit Account'),
                ['action' => 'edit', $account->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Delete Account'),
                ['action' => 'delete', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $account->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List Accounts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__d('radius', 'New Account'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="accounts view content">
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
                            <td><?= $this->Number->format($account->type) ?></td>
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
                            <td><?= $this->Number->format($account->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Modified') ?></th>
                            <td><?= h($account->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Modified By') ?></th>
                            <td><?= $this->Number->format($account->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __d('radius', 'Related Radcheck') ?></h4>
                <?php if (!empty($account->radcheck)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Id') ?></th>
                            <th><?= __d('radius', 'Username') ?></th>
                            <th><?= __d('radius', 'Attribute') ?></th>
                            <th><?= __d('radius', 'Op') ?></th>
                            <th><?= __d('radius', 'Value') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radcheck as $radcheck) : ?>
                        <tr>
                            <td><?= h($radcheck->id) ?></td>
                            <td><?= h($radcheck->username) ?></td>
                            <td><?= h($radcheck->attribute) ?></td>
                            <td><?= h($radcheck->op) ?></td>
                            <td><?= h($radcheck->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radcheck', 'action' => 'view', $radcheck->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radcheck', 'action' => 'edit', $radcheck->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radcheck', 'action' => 'delete', $radcheck->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radcheck->id
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
                <h4><?= __d('radius', 'Related Radreply') ?></h4>
                <?php if (!empty($account->radreply)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Id') ?></th>
                            <th><?= __d('radius', 'Username') ?></th>
                            <th><?= __d('radius', 'Attribute') ?></th>
                            <th><?= __d('radius', 'Op') ?></th>
                            <th><?= __d('radius', 'Value') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radreply as $radreply) : ?>
                        <tr>
                            <td><?= h($radreply->id) ?></td>
                            <td><?= h($radreply->username) ?></td>
                            <td><?= h($radreply->attribute) ?></td>
                            <td><?= h($radreply->op) ?></td>
                            <td><?= h($radreply->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radreply', 'action' => 'view', $radreply->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radreply', 'action' => 'edit', $radreply->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radreply', 'action' => 'delete', $radreply->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radreply->id
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
                <h4><?= __d('radius', 'Related Radusergroup') ?></h4>
                <?php if (!empty($account->radusergroup)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Id') ?></th>
                            <th><?= __d('radius', 'Username') ?></th>
                            <th><?= __d('radius', 'Groupname') ?></th>
                            <th><?= __d('radius', 'Priority') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radusergroup as $radusergroup) : ?>
                        <tr>
                            <td><?= h($radusergroup->id) ?></td>
                            <td><?= h($radusergroup->username) ?></td>
                            <td><?= h($radusergroup->groupname) ?></td>
                            <td><?= h($radusergroup->priority) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radusergroup', 'action' => 'view', $radusergroup->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radusergroup', 'action' => 'edit', $radusergroup->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
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
            <div class="related">
                <h4><?= __d('radius', 'Related Radpostauth') ?></h4>
                <?php if (!empty($account->radpostauth)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Id') ?></th>
                            <th><?= __d('radius', 'Username') ?></th>
                            <th><?= __d('radius', 'Pass') ?></th>
                            <th><?= __d('radius', 'Reply') ?></th>
                            <th><?= __d('radius', 'Calledstationid') ?></th>
                            <th><?= __d('radius', 'Callingstationid') ?></th>
                            <th><?= __d('radius', 'Authdate') ?></th>
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
            <div class="related">
                <h4><?= __d('radius', 'Related Radacct') ?></h4>
                <?php if (!empty($account->radacct)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Radacctid') ?></th>
                            <th><?= __d('radius', 'Acctsessionid') ?></th>
                            <th><?= __d('radius', 'Acctuniqueid') ?></th>
                            <th><?= __d('radius', 'Username') ?></th>
                            <th><?= __d('radius', 'Realm') ?></th>
                            <th><?= __d('radius', 'Nasipaddress') ?></th>
                            <th><?= __d('radius', 'Nasportid') ?></th>
                            <th><?= __d('radius', 'Nasporttype') ?></th>
                            <th><?= __d('radius', 'Acctstarttime') ?></th>
                            <th><?= __d('radius', 'Acctupdatetime') ?></th>
                            <th><?= __d('radius', 'Acctstoptime') ?></th>
                            <th><?= __d('radius', 'Acctinterval') ?></th>
                            <th><?= __d('radius', 'Acctsessiontime') ?></th>
                            <th><?= __d('radius', 'Acctauthentic') ?></th>
                            <th><?= __d('radius', 'Connectinfo Start') ?></th>
                            <th><?= __d('radius', 'Connectinfo Stop') ?></th>
                            <th><?= __d('radius', 'Acctinputoctets') ?></th>
                            <th><?= __d('radius', 'Acctoutputoctets') ?></th>
                            <th><?= __d('radius', 'Calledstationid') ?></th>
                            <th><?= __d('radius', 'Callingstationid') ?></th>
                            <th><?= __d('radius', 'Acctterminatecause') ?></th>
                            <th><?= __d('radius', 'Servicetype') ?></th>
                            <th><?= __d('radius', 'Framedprotocol') ?></th>
                            <th><?= __d('radius', 'Framedipaddress') ?></th>
                            <th><?= __d('radius', 'Framedipv6address') ?></th>
                            <th><?= __d('radius', 'Framedipv6prefix') ?></th>
                            <th><?= __d('radius', 'Framedinterfaceid') ?></th>
                            <th><?= __d('radius', 'Delegatedipv6prefix') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radacct as $radacct) : ?>
                        <tr>
                            <td><?= h($radacct->radacctid) ?></td>
                            <td><?= h($radacct->acctsessionid) ?></td>
                            <td><?= h($radacct->acctuniqueid) ?></td>
                            <td><?= h($radacct->username) ?></td>
                            <td><?= h($radacct->realm) ?></td>
                            <td><?= h($radacct->nasipaddress) ?></td>
                            <td><?= h($radacct->nasportid) ?></td>
                            <td><?= h($radacct->nasporttype) ?></td>
                            <td><?= h($radacct->acctstarttime) ?></td>
                            <td><?= h($radacct->acctupdatetime) ?></td>
                            <td><?= h($radacct->acctstoptime) ?></td>
                            <td><?= h($radacct->acctinterval) ?></td>
                            <td><?= h($radacct->acctsessiontime) ?></td>
                            <td><?= h($radacct->acctauthentic) ?></td>
                            <td><?= h($radacct->connectinfo_start) ?></td>
                            <td><?= h($radacct->connectinfo_stop) ?></td>
                            <td><?= h($radacct->acctinputoctets) ?></td>
                            <td><?= h($radacct->acctoutputoctets) ?></td>
                            <td><?= h($radacct->calledstationid) ?></td>
                            <td><?= h($radacct->callingstationid) ?></td>
                            <td><?= h($radacct->acctterminatecause) ?></td>
                            <td><?= h($radacct->servicetype) ?></td>
                            <td><?= h($radacct->framedprotocol) ?></td>
                            <td><?= h($radacct->framedipaddress) ?></td>
                            <td><?= h($radacct->framedipv6address) ?></td>
                            <td><?= h($radacct->framedipv6prefix) ?></td>
                            <td><?= h($radacct->framedinterfaceid) ?></td>
                            <td><?= h($radacct->delegatedipv6prefix) ?></td>
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
        </div>
    </div>
</div>
