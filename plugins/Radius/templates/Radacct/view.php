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
            <?= $this->Html->link(
                __d('radius', 'Edit RADIUS Accounting'),
                ['action' => 'edit', $radacct->radacctid],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Delete RADIUS Accounting'),
                ['action' => 'delete', $radacct->radacctid],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radacct->radacctid),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(__d('radius', 'List RADIUS Accountings'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__d('radius', 'New RADIUS Accounting'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
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
                    <th><?= __d('radius', 'Nasipaddress') ?></th>
                    <td><?= h($radacct->nasipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Framedipaddress') ?></th>
                    <td><?= h($radacct->framedipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Framedipv6address') ?></th>
                    <td><?= h($radacct->framedipv6address) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Framedipv6prefix') ?></th>
                    <td><?= h($radacct->framedipv6prefix) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Delegatedipv6prefix') ?></th>
                    <td><?= h($radacct->delegatedipv6prefix) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Radacctid') ?></th>
                    <td><?= $this->Number->format($radacct->radacctid) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Acctinterval') ?></th>
                    <td><?= $this->Number->format($radacct->acctinterval) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Acctsessiontime') ?></th>
                    <td><?= $this->Number->format($radacct->acctsessiontime) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Acctinputoctets') ?></th>
                    <td><?= $this->Number->format($radacct->acctinputoctets) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Acctoutputoctets') ?></th>
                    <td><?= $this->Number->format($radacct->acctoutputoctets) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Acctstarttime') ?></th>
                    <td><?= h($radacct->acctstarttime) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Acctupdatetime') ?></th>
                    <td><?= h($radacct->acctupdatetime) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Acctstoptime') ?></th>
                    <td><?= h($radacct->acctstoptime) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __d('radius', 'Acctsessionid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctsessionid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Acctuniqueid') ?></strong>
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
                <strong><?= __d('radius', 'Nasportid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->nasportid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Nasporttype') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->nasporttype)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Acctauthentic') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctauthentic)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Connectinfo Start') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->connectinfo_start)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Connectinfo Stop') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->connectinfo_stop)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Calledstationid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->calledstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Callingstationid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->callingstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Acctterminatecause') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctterminatecause)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Servicetype') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->servicetype)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Framedprotocol') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->framedprotocol)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Framedinterfaceid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->framedinterfaceid)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
