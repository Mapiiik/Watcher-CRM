<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radacct
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'Edit RADIUS Accounting'),
                ['action' => 'edit', $radacct->radacctid],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Accounting'),
                ['action' => 'delete', $radacct->radacctid],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radacct->radacctid),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Accountings'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'New RADIUS Accounting'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radacct view content">
            <h3><?= h($radacct->radacctid) ?></h3>
            <table>
                <tr>
                    <th><?= __d('radius', 'Username') ?></th>
                    <td><?= $radacct->has('account') ? $this->Html->link(
                        $radacct->account->username,
                        ['controller' => 'Accounts', 'action' => 'view', $radacct->account->id]
                    ) : h($radacct->username) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'NAS IP Address') ?></th>
                    <td><?= h($radacct->nasipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Framed IP Address') ?></th>
                    <td><?= h($radacct->framedipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Framed IPv6 Address') ?></th>
                    <td><?= h($radacct->framedipv6address) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Framed IPv6 Prefix') ?></th>
                    <td><?= h($radacct->framedipv6prefix) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Delegated IPv6 Prefix') ?></th>
                    <td><?= h($radacct->delegatedipv6prefix) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Uploaded') ?></th>
                    <td><?= $this->Number->toReadableSize($radacct->acctinputoctets) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Downloaded') ?></th>
                    <td><?= $this->Number->toReadableSize($radacct->acctoutputoctets) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Start Time') ?></th>
                    <td><?= h($radacct->acctstarttime) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Update Time') ?></th>
                    <td><?= h($radacct->acctupdatetime) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Update Interval') ?></th>
                    <td><?= $this->Number->format($radacct->acctinterval) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Stop Time') ?></th>
                    <td><?= h($radacct->acctstoptime) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Session Time') ?></th>
                    <td><?= $this->Number->format($radacct->acctsessiontime) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'RADIUS Accounting ID') ?></th>
                    <td><?= $this->Number->format($radacct->radacctid) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __d('radius', 'Session ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctsessionid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Unique ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctuniqueid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Realm') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->realm)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'NAS Port ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->nasportid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'NAS Port Type') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->nasporttype)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Authenticated Via') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctauthentic)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Connection Info Start') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->connectinfo_start)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Connection Info Stop') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->connectinfo_stop)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Called Station ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->calledstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Calling Station ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->callingstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Termination Cause') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctterminatecause)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Service Type') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->servicetype)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Framed Protocol') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->framedprotocol)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Framed Interface ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->framedinterfaceid)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
