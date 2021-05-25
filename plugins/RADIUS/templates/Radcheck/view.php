<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radcheck'), ['action' => 'edit', $radcheck->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radcheck'), ['action' => 'delete', $radcheck->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radcheck->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radcheck'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radcheck'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radcheck view content">
            <h3><?= h($radcheck->username) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $radcheck->has('customer') ? $this->Html->link($radcheck->customer->name, ['controller' => 'Customers', 'action' => 'view', $radcheck->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $radcheck->has('contract') ? $this->Html->link($radcheck->contract->number, ['controller' => 'Contracts', 'action' => 'view', $radcheck->contract->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= h($radcheck->username) ?></td>
                </tr>
                <tr>
                    <th><?= __('Attribute') ?></th>
                    <td><?= h($radcheck->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __('Op') ?></th>
                    <td><?= h($radcheck->op) ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($radcheck->value) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type') ?></th>
                    <td><?= $this->Number->format($radcheck->type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radcheck->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($radcheck->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($radcheck->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($radcheck->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($radcheck->modified_by) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Radreply') ?></h4>
                <?php if (!empty($radcheck->radreply)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Username') ?></th>
                            <th><?= __('Attribute') ?></th>
                            <th><?= __('Op') ?></th>
                            <th><?= __('Value') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radcheck->radreply as $radreply) : ?>
                        <tr>
                            <td><?= h($radreply->id) ?></td>
                            <td><?= h($radreply->username) ?></td>
                            <td><?= h($radreply->attribute) ?></td>
                            <td><?= h($radreply->op) ?></td>
                            <td><?= h($radreply->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Radreply', 'action' => 'view', $radreply->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Radreply', 'action' => 'edit', $radreply->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Radreply', 'action' => 'delete', $radreply->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radreply->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Radusergroup') ?></h4>
                <?php if (!empty($radcheck->radusergroup)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Username') ?></th>
                            <th><?= __('Groupname') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radcheck->radusergroup as $radusergroup) : ?>
                        <tr>
                            <td><?= h($radusergroup->id) ?></td>
                            <td><?= h($radusergroup->username) ?></td>
                            <td><?= h($radusergroup->groupname) ?></td>
                            <td><?= h($radusergroup->priority) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Radusergroup', 'action' => 'view', $radusergroup->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Radusergroup', 'action' => 'edit', $radusergroup->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Radusergroup', 'action' => 'delete', $radusergroup->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radusergroup->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Radpostauth') ?></h4>
                <?php if (!empty($radcheck->radpostauth)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Username') ?></th>
                            <th><?= __('Pass') ?></th>
                            <th><?= __('Reply') ?></th>
                            <th><?= __('Calledstationid') ?></th>
                            <th><?= __('Callingstationid') ?></th>
                            <th><?= __('Authdate') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radcheck->radpostauth as $radpostauth) : ?>
                        <tr>
                            <td><?= h($radpostauth->id) ?></td>
                            <td><?= h($radpostauth->username) ?></td>
                            <td><?= h($radpostauth->pass) ?></td>
                            <td><?= h($radpostauth->reply) ?></td>
                            <td><?= h($radpostauth->calledstationid) ?></td>
                            <td><?= h($radpostauth->callingstationid) ?></td>
                            <td><?= h($radpostauth->authdate) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Radpostauth', 'action' => 'view', $radpostauth->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Radpostauth', 'action' => 'edit', $radpostauth->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Radpostauth', 'action' => 'delete', $radpostauth->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radpostauth->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Radacct') ?></h4>
                <?php if (!empty($radcheck->radacct)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Radacctid') ?></th>
                            <th><?= __('Acctsessionid') ?></th>
                            <th><?= __('Acctuniqueid') ?></th>
                            <th><?= __('Username') ?></th>
                            <th><?= __('Realm') ?></th>
                            <th><?= __('Nasipaddress') ?></th>
                            <th><?= __('Nasportid') ?></th>
                            <th><?= __('Nasporttype') ?></th>
                            <th><?= __('Acctstarttime') ?></th>
                            <th><?= __('Acctupdatetime') ?></th>
                            <th><?= __('Acctstoptime') ?></th>
                            <th><?= __('Acctinterval') ?></th>
                            <th><?= __('Acctsessiontime') ?></th>
                            <th><?= __('Acctauthentic') ?></th>
                            <th><?= __('Connectinfo Start') ?></th>
                            <th><?= __('Connectinfo Stop') ?></th>
                            <th><?= __('Acctinputoctets') ?></th>
                            <th><?= __('Acctoutputoctets') ?></th>
                            <th><?= __('Calledstationid') ?></th>
                            <th><?= __('Callingstationid') ?></th>
                            <th><?= __('Acctterminatecause') ?></th>
                            <th><?= __('Servicetype') ?></th>
                            <th><?= __('Framedprotocol') ?></th>
                            <th><?= __('Framedipaddress') ?></th>
                            <th><?= __('Framedipv6address') ?></th>
                            <th><?= __('Framedipv6prefix') ?></th>
                            <th><?= __('Framedinterfaceid') ?></th>
                            <th><?= __('Delegatedipv6prefix') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radcheck->radacct as $radacct) : ?>
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
                                <?= $this->Html->link(__('View'), ['controller' => 'Radacct', 'action' => 'view', $radacct->radacctid]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Radacct', 'action' => 'edit', $radacct->radacctid]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Radacct', 'action' => 'delete', $radacct->radacctid], ['confirm' => __('Are you sure you want to delete # {0}?', $radacct->radacctid)]) ?>
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
