<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $radacct
 */
?>
<div class="radacct index content">
    <?= $this->Html->link(__('New Radacct'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Radacct') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('radacctid') ?></th>
                    <th><?= $this->Paginator->sort('acctsessionid') ?></th>
                    <th><?= $this->Paginator->sort('acctuniqueid') ?></th>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('realm') ?></th>
                    <th><?= $this->Paginator->sort('nasipaddress') ?></th>
                    <th><?= $this->Paginator->sort('nasportid') ?></th>
                    <th><?= $this->Paginator->sort('nasporttype') ?></th>
                    <th><?= $this->Paginator->sort('acctstarttime') ?></th>
                    <th><?= $this->Paginator->sort('acctupdatetime') ?></th>
                    <th><?= $this->Paginator->sort('acctstoptime') ?></th>
                    <th><?= $this->Paginator->sort('acctinterval') ?></th>
                    <th><?= $this->Paginator->sort('acctsessiontime') ?></th>
                    <th><?= $this->Paginator->sort('acctauthentic') ?></th>
                    <th><?= $this->Paginator->sort('connectinfo_start') ?></th>
                    <th><?= $this->Paginator->sort('connectinfo_stop') ?></th>
                    <th><?= $this->Paginator->sort('acctinputoctets') ?></th>
                    <th><?= $this->Paginator->sort('acctoutputoctets') ?></th>
                    <th><?= $this->Paginator->sort('calledstationid') ?></th>
                    <th><?= $this->Paginator->sort('callingstationid') ?></th>
                    <th><?= $this->Paginator->sort('acctterminatecause') ?></th>
                    <th><?= $this->Paginator->sort('servicetype') ?></th>
                    <th><?= $this->Paginator->sort('framedprotocol') ?></th>
                    <th><?= $this->Paginator->sort('framedipaddress') ?></th>
                    <th><?= $this->Paginator->sort('framedipv6address') ?></th>
                    <th><?= $this->Paginator->sort('framedipv6prefix') ?></th>
                    <th><?= $this->Paginator->sort('delegatedipv6prefix') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radacct as $radacct): ?>
                <tr>
                    <td><?= $this->Number->format($radacct->radacctid) ?></td>
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
                    <td><?= $this->Number->format($radacct->acctinterval) ?></td>
                    <td><?= $this->Number->format($radacct->acctsessiontime) ?></td>
                    <td><?= h($radacct->acctauthentic) ?></td>
                    <td><?= h($radacct->connectinfo_start) ?></td>
                    <td><?= h($radacct->connectinfo_stop) ?></td>
                    <td><?= $this->Number->format($radacct->acctinputoctets) ?></td>
                    <td><?= $this->Number->format($radacct->acctoutputoctets) ?></td>
                    <td><?= h($radacct->calledstationid) ?></td>
                    <td><?= h($radacct->callingstationid) ?></td>
                    <td><?= h($radacct->acctterminatecause) ?></td>
                    <td><?= h($radacct->servicetype) ?></td>
                    <td><?= h($radacct->framedprotocol) ?></td>
                    <td><?= h($radacct->framedipaddress) ?></td>
                    <td><?= h($radacct->framedipv6address) ?></td>
                    <td><?= h($radacct->framedipv6prefix) ?></td>
                    <td><?= h($radacct->delegatedipv6prefix) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $radacct->radacctid]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $radacct->radacctid]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $radacct->radacctid], ['confirm' => __('Are you sure you want to delete # {0}?', $radacct->radacctid)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
