<?php
use Cake\Core\Plugin;

/**
 * @var \App\View\AppView $this
 */
?>
<div class="overviews index content">
    <?= $this->heading(__('Overviews')) ?>
    <div class="table-responsive">
        <div class="related">
            <h4><?= __('Documentation Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __d('app_files', 'List Documentations'),
                    [
                        'controller' => 'Documentations',
                        'action' => 'index',
                        'plugin' => null,
                        'customer_id' => false,
                        'contract_id' => false,
                    ],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <?php if (Plugin::isLoaded('WorkReports')) : ?>
        <div class="related">
            <h4><?= __('Work Report Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Work to Invoice'),
                    [
                        'plugin' => 'WorkReports',
                        'controller' => 'WorkOverviews',
                        'action' => 'toInvoice',
                        'customer_id' => false,
                        'contract_id' => false,
                    ],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Work at Access Points'),
                    [
                        'plugin' => 'WorkReports',
                        'controller' => 'WorkOverviews',
                        'action' => 'byAccessPoint',
                        'customer_id' => false,
                        'contract_id' => false,
                    ],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="related">
            <h4><?= __('Service Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Overview of Active Services'),
                    ['action' => 'overviewOfActiveServices'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Czech Customer Connection Points') . ' (' . __('Reports for CTO') . ')',
                    ['action' => 'overviewOfCzechCustomerConnectionPoints'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Czech Customer Connection Speeds') . ' (' . __('Reports for CTO') . ')',
                    ['action' => 'overviewOfCzechCustomerConnectionSpeeds'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Croatian Quarterly Report') . ' (' . __('Reports for HAKOM') . ')',
                    ['action' => 'overviewOfCroatianQuarterlyReport'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Croatian Customer Connection Points') . ' (' . __('Reports for HAKOM') . ')',
                    ['action' => 'overviewOfCroatianConnectionPoints'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Available Connections'),
                    ['controller' => 'AvailableConnections', 'action' => 'index'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Dealer Commissions'),
                    ['action' => 'overviewOfDealerCommissions'],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Customer Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Customer Problems'),
                    ['action' => 'overviewOfCustomerProblems'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Address Problems'),
                    ['action' => 'overviewOfAddressProblems'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Customer Labels'),
                    ['controller' => 'CustomerLabels', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Emails'),
                    ['controller' => 'Emails', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Phones'),
                    ['controller' => 'Phones', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Logins'),
                    ['controller' => 'Logins', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Addresses'),
                    ['controller' => 'Addresses', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Contracts'),
                    ['controller' => 'Contracts', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Documents'),
                    ['controller' => 'Documents', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Customer Messages'),
                    ['controller' => 'CustomerMessages', 'action' => 'index'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Access Credentials'),
                    ['controller' => 'AccessCredentials', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Contract Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Contract Problems'),
                    ['action' => 'overviewOfContractProblems'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Contracts'),
                    ['action' => 'overviewOfContracts'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of New and Ending Contracts'),
                    ['action' => 'overviewOfNewAndEndingContracts'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of New and Ending Billings'),
                    ['action' => 'overviewOfNewAndEndingBillings'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Contract Versions'),
                    ['controller' => 'ContractVersions', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Billings'),
                    ['controller' => 'Billings', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Service Overrides'),
                    ['controller' => 'ServiceOverrides', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Borrowed Equipments'),
                    ['controller' => 'BorrowedEquipments', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Sold Equipments'),
                    ['controller' => 'SoldEquipments', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List IP Addresses'),
                    ['controller' => 'IpAddresses', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List IP Networks'),
                    ['controller' => 'IpNetworks', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Removed IP Addresses'),
                    ['controller' => 'RemovedIpAddresses', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Removed IP Networks'),
                    ['controller' => 'RemovedIpNetworks', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Historical Connections'),
                    ['controller' => 'HistoricalConnections', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>
    </div>
</div>
