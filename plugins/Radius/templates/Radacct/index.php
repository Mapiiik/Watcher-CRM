<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Radius\Model\Entity\Radacct> $radaccts
 */
?>
<div class="radacct index content">
    <?= $this->AuthLink->link(
        __d('radius', 'New RADIUS Accounting'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'RADIUS Accountings') ?></h3>
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
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radaccts as $radacct) : ?>
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
                    <td><?= $radacct->acctinterval === null ?
                        '' : $this->Number->format($radacct->acctinterval) ?></td>
                    <td><?= $radacct->acctsessiontime === null ?
                        '' : $this->Number->format($radacct->acctsessiontime) ?></td>
                    <td><?= h($radacct->acctauthentic) ?></td>
                    <td><?= h($radacct->connectinfo_start) ?></td>
                    <td><?= h($radacct->connectinfo_stop) ?></td>
                    <td><?= $radacct->acctinputoctets === null ?
                        '' : $this->Number->format($radacct->acctinputoctets) ?></td>
                    <td><?= $radacct->acctoutputoctets === null ?
                        '' : $this->Number->format($radacct->acctoutputoctets) ?></td>
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
                        <?= $this->AuthLink->link(__d('radius', 'View'), ['action' => 'view', $radacct->radacctid]) ?>
                        <?= $this->AuthLink->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $radacct->radacctid],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $radacct->radacctid],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radacct->radacctid)]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
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
        <p><?= $this->Paginator->counter(
            __d('radius', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
