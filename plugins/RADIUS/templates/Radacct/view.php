<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radacct
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radacct'), ['action' => 'edit', $radacct->radacctid], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radacct'), ['action' => 'delete', $radacct->radacctid], ['confirm' => __('Are you sure you want to delete # {0}?', $radacct->radacctid), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radacct'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radacct'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radacct view content">
            <h3><?= h($radacct->radacctid) ?></h3>
            <table>
                <tr>
                    <th><?= __('Acctsessionid') ?></th>
                    <td><?= h($radacct->acctsessionid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctuniqueid') ?></th>
                    <td><?= h($radacct->acctuniqueid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= h($radacct->username) ?></td>
                </tr>
                <tr>
                    <th><?= __('Realm') ?></th>
                    <td><?= h($radacct->realm) ?></td>
                </tr>
                <tr>
                    <th><?= __('Nasipaddress') ?></th>
                    <td><?= h($radacct->nasipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __('Nasportid') ?></th>
                    <td><?= h($radacct->nasportid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Nasporttype') ?></th>
                    <td><?= h($radacct->nasporttype) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctauthentic') ?></th>
                    <td><?= h($radacct->acctauthentic) ?></td>
                </tr>
                <tr>
                    <th><?= __('Connectinfo Start') ?></th>
                    <td><?= h($radacct->connectinfo_start) ?></td>
                </tr>
                <tr>
                    <th><?= __('Connectinfo Stop') ?></th>
                    <td><?= h($radacct->connectinfo_stop) ?></td>
                </tr>
                <tr>
                    <th><?= __('Calledstationid') ?></th>
                    <td><?= h($radacct->calledstationid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Callingstationid') ?></th>
                    <td><?= h($radacct->callingstationid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctterminatecause') ?></th>
                    <td><?= h($radacct->acctterminatecause) ?></td>
                </tr>
                <tr>
                    <th><?= __('Servicetype') ?></th>
                    <td><?= h($radacct->servicetype) ?></td>
                </tr>
                <tr>
                    <th><?= __('Framedprotocol') ?></th>
                    <td><?= h($radacct->framedprotocol) ?></td>
                </tr>
                <tr>
                    <th><?= __('Framedipaddress') ?></th>
                    <td><?= h($radacct->framedipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __('Groupname') ?></th>
                    <td><?= h($radacct->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __('Xascendsessionsvrkey') ?></th>
                    <td><?= h($radacct->xascendsessionsvrkey) ?></td>
                </tr>
                <tr>
                    <th><?= __('Radacctid') ?></th>
                    <td><?= $this->Number->format($radacct->radacctid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctsessiontime') ?></th>
                    <td><?= $this->Number->format($radacct->acctsessiontime) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctinputoctets') ?></th>
                    <td><?= $this->Number->format($radacct->acctinputoctets) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctoutputoctets') ?></th>
                    <td><?= $this->Number->format($radacct->acctoutputoctets) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctstartdelay') ?></th>
                    <td><?= $this->Number->format($radacct->acctstartdelay) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctstopdelay') ?></th>
                    <td><?= $this->Number->format($radacct->acctstopdelay) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctstarttime') ?></th>
                    <td><?= h($radacct->acctstarttime) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctstoptime') ?></th>
                    <td><?= h($radacct->acctstoptime) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
