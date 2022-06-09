<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="settings index content">
    <h3><?= __('Settings') ?></h3>
    <div class="table-responsive">
        <div class="related">
            <h4><?= __('User Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('Change Password'),
                    ['controller' => 'Users', 'action' => 'changePassword', 'plugin' => 'CakeDC/Users'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('User Profile'),
                    ['controller' => 'Users', 'action' => 'profile', 'plugin' => 'CakeDC/Users'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Users'),
                    ['controller' => 'Users', 'action' => 'index', 'plugin' => 'CakeDC/Users'],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('System Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Labels'),
                    ['controller' => 'Labels', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Countries'),
                    ['controller' => 'Countries', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Tax Rates'),
                    ['controller' => 'Tax Rates', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Service Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Services'),
                    ['controller' => 'Services', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Service Types'),
                    ['controller' => 'ServiceTypes', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Queues'),
                    ['controller' => 'Queues', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Equipment Types'),
                    ['controller' => 'EquipmentTypes', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Task Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Task States'),
                    ['controller' => 'TaskStates', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Task Types'),
                    ['controller' => 'Task Types', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __('Commission Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __('List Commissions'),
                    ['controller' => 'Commissions', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __('List Dealer Commissions'),
                    ['controller' => 'DealerCommissions', 'action' => 'index', 'plugin' => null],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>
    </div>
</div>
