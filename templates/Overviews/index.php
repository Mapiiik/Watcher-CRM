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
                    __('Overview of active services'),
                    ['action' => 'overviewOfActiveServices'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of customer connection points'),
                    ['action' => 'overviewOfCustomerConnectionPoints'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('Overview of dealer commissions'),
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
            </div>
        </div>

        <div class="related">
            <h4><?= __('Contract Related') ?></h4>
            <div>
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
                    __('List Ips'),
                    ['controller' => 'Ips', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Ip Networks'),
                    ['controller' => 'IpNetworks', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Removed Ips'),
                    ['controller' => 'RemovedIps', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Removed Ip Networks'),
                    ['controller' => 'RemovedIpNetworks', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>
    </div>
</div>
