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
    <div class="column-responsive column-90">
        <div class="radacct view content">
            <h3><?= h($radacct->radacctid) ?></h3>
            <table>
                <tr>
                    <th><?= __('Radcheck') ?></th>
                    <td><?= $radacct->has('radcheck') ? $this->Html->link($radacct->radcheck->username, ['controller' => 'Radcheck', 'action' => 'view', $radacct->radcheck->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Nasipaddress') ?></th>
                    <td><?= h($radacct->nasipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __('Framedipaddress') ?></th>
                    <td><?= h($radacct->framedipaddress) ?></td>
                </tr>
                <tr>
                    <th><?= __('Framedipv6address') ?></th>
                    <td><?= h($radacct->framedipv6address) ?></td>
                </tr>
                <tr>
                    <th><?= __('Framedipv6prefix') ?></th>
                    <td><?= h($radacct->framedipv6prefix) ?></td>
                </tr>
                <tr>
                    <th><?= __('Delegatedipv6prefix') ?></th>
                    <td><?= h($radacct->delegatedipv6prefix) ?></td>
                </tr>
                <tr>
                    <th><?= __('Radacctid') ?></th>
                    <td><?= $this->Number->format($radacct->radacctid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctinterval') ?></th>
                    <td><?= $this->Number->format($radacct->acctinterval) ?></td>
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
                    <th><?= __('Acctstarttime') ?></th>
                    <td><?= h($radacct->acctstarttime) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctupdatetime') ?></th>
                    <td><?= h($radacct->acctupdatetime) ?></td>
                </tr>
                <tr>
                    <th><?= __('Acctstoptime') ?></th>
                    <td><?= h($radacct->acctstoptime) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Acctsessionid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctsessionid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Acctuniqueid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctuniqueid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Realm') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->realm)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Nasportid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->nasportid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Nasporttype') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->nasporttype)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Acctauthentic') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctauthentic)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Connectinfo Start') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->connectinfo_start)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Connectinfo Stop') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->connectinfo_stop)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Calledstationid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->calledstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Callingstationid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->callingstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Acctterminatecause') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->acctterminatecause)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Servicetype') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->servicetype)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Framedprotocol') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->framedprotocol)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Framedinterfaceid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radacct->framedinterfaceid)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
