<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="overviews index content">
    <h3><?= __('Overviews') ?></h3>
    <div class="table-responsive">
        <div class="related">
            <h4><?= __('Service Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Overview of Active Services'),
                    ['action' => 'overviewOfActiveServices'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Customer Connection Speeds') . ' (' . __('Reports for CTO') . ')',
                    ['action' => 'overviewOfCustomerConnectionSpeeds'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Customer Connection Points') . ' (' . __('Reports for CTO') . ')',
                    ['action' => 'overviewOfCustomerConnectionPoints'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Customer Connection Points (Obsolete)') . ' (' . __('Reports for CTO') . ')',
                    ['action' => 'overviewOfCustomerConnectionPointsObsolete'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of Dealer Commissions'),
                    ['action' => 'overviewOfDealerCommissions'],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Customer Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Customer Labels'),
                    ['controller' => 'CustomerLabels', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Emails'),
                    ['controller' => 'Emails', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Phones'),
                    ['controller' => 'Phones', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Logins'),
                    ['controller' => 'Logins', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Addresses'),
                    ['controller' => 'Addresses', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Contracts'),
                    ['controller' => 'Contracts', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Customer Messages'),
                    ['controller' => 'CustomerMessages', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Access Credentials'),
                    ['controller' => 'AccessCredentials', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Contract Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Contract Versions'),
                    ['controller' => 'ContractVersions', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Billings'),
                    ['controller' => 'Billings', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Borrowed Equipments'),
                    ['controller' => 'BorrowedEquipments', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Sold Equipments'),
                    ['controller' => 'SoldEquipments', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List IP Addresses'),
                    ['controller' => 'IpAddresses', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List IP Networks'),
                    ['controller' => 'IpNetworks', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Removed IP Addresses'),
                    ['controller' => 'RemovedIpAddresses', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Removed IP Networks'),
                    ['controller' => 'RemovedIpNetworks', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>
    </div>
</div>
