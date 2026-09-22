<?php
use Cake\Core\Plugin;

/**
 * @var \App\View\AppView $this
 */
?>
<div class="settings index content">
    <?= $this->heading(__('Settings')) ?>
    <div class="table-responsive">
        <div class="related">
            <h4><?= __('User Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('User Profile'),
                    ['controller' => 'AppUsers', 'action' => 'profile'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Users'),
                    ['controller' => 'AppUsers', 'action' => 'index'],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Application Settings') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Company Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.company'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Customers Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.customers'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Business Register Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.business_register'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Documents Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.documents'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Contracts Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.contracts'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Emails Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.emails'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Customer Messages Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.customer_messages'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Dashboard Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'core.dashboard'],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Bookkeeping Settings') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Invoices Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'bookkeeping.invoices'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Debtors Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'bookkeeping.debtors'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Accounting Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'bookkeeping.accounting'],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Billing Functions') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Bulk Service Change'),
                    ['controller' => 'Billings', 'action' => 'bulkServiceChange', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Customer Functions') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Identity Number Check'),
                    ['controller' => 'Customers', 'action' => 'identityNumberCheck', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Update Phone Number Format'),
                    [
                        'controller' => 'Phones',
                        'action' => 'formatAll',
                        'plugin' => null,
                        false,
                    ],
                    [
                        'confirm' => __(
                            'Do you really want to update all phones for all customers?',
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Update All Addresses from National Address Registries'),
                    [
                        'controller' => 'Addresses',
                        'action' => 'updateAllFromNationalAddressRegistries',
                        'plugin' => null,
                        false,
                    ],
                    [
                        'confirm' => __(
                            'Do you really want to update all addresses from the national address registries?',
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Contract Functions') ?></h4>
            <div>
                <?= $this->AuthLink->postLink(
                    __('Addition of Contract Numbers'),
                    [
                        'controller' => 'Contracts',
                        'action' => 'updateAllNumbers',
                        'plugin' => null,
                        false,
                    ],
                    [
                        'confirm' => __(
                            'Do you really want to add a contract number for all contracts?',
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Addition of Contract Numbers') . ' (' . __('Force Overwrite') . ')',
                    [
                        'controller' => 'Contracts',
                        'action' => 'updateAllNumbers',
                        'plugin' => null,
                        true,
                    ],
                    [
                        'confirm' => __(
                            'Do you really want to re-set a contract number for all contracts?',
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Addition of Subscriber Verification Codes'),
                    [
                        'controller' => 'Contracts',
                        'action' => 'updateAllSubscriberVerificationCodes',
                        'plugin' => null,
                        false,
                    ],
                    [
                        'confirm' => __(
                            'Do you really want to add a subscriber verification code for all contracts?',
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Addition of Subscriber Verification Codes') . ' (' . __('Force Overwrite') . ')',
                    [
                        'controller' => 'Contracts',
                        'action' => 'updateAllSubscriberVerificationCodes',
                        'plugin' => null,
                        true,
                    ],
                    [
                        'confirm' => __(
                            'Do you really want to re-set a subscriber verification code for all contracts?',
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Bulk IP Address Reassignment'),
                    ['controller' => 'IpAddresses', 'action' => 'bulkReassignment', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('System Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Labels'),
                    ['controller' => 'Labels', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Countries'),
                    ['controller' => 'Countries', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Accounting Profiles'),
                    ['controller' => 'AccountingProfiles', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Contract Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Contract States'),
                    ['controller' => 'ContractStates', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Service Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Services'),
                    ['controller' => 'Services', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Service Types'),
                    ['controller' => 'ServiceTypes', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Connection Profiles'),
                    ['controller' => 'ConnectionProfiles', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Available Connections'),
                    ['controller' => 'AvailableConnections', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Equipment Types'),
                    ['controller' => 'EquipmentTypes', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Documentation Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __d('app_files', 'List Documentation Types'),
                    ['controller' => 'DocumentationTypes', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Task Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Task States'),
                    ['controller' => 'TaskStates', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Task Types'),
                    ['controller' => 'TaskTypes', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>

        <?php if (Plugin::isLoaded('WorkReports')) : ?>
        <div class="related">
            <h4><?= __('Work Report Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Work Report Item Types'),
                    ['controller' => 'WorkReportItemTypes', 'action' => 'index', 'plugin' => 'WorkReports'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Work Rates'),
                    ['controller' => 'WorkRates', 'action' => 'index', 'plugin' => 'WorkReports'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Work Labels'),
                    ['controller' => 'WorkLabels', 'action' => 'index', 'plugin' => 'WorkReports'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Work Cars'),
                    ['controller' => 'WorkCars', 'action' => 'index', 'plugin' => 'WorkReports'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Work Report Workers'),
                    ['controller' => 'WorkReportWorkers', 'action' => 'index', 'plugin' => 'WorkReports'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Work Calendar Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'work_reports.calendar'],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('On Call Hours Configuration'),
                    ['controller' => 'Settings', 'action' => 'edit', 'work_reports.on_call_hours'],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="related">
            <h4><?= __('Commission Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Commissions'),
                    ['controller' => 'Commissions', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Dealer Commissions'),
                    ['controller' => 'DealerCommissions', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item'],
                ) ?>
            </div>
        </div>
    </div>
</div>
