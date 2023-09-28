<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="settings index content">
    <h3><?= __d('radius', 'RADIUS Settings') ?></h3>
    <div class="table-responsive">
        <div class="related">
            <h4><?= __d('radius', 'RADIUS Account Functions') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __d('radius', 'Update Related Records'),
                    ['controller' => 'Accounts', 'action' => 'updateRelatedRecordsForAllAccounts'],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __d('radius', 'RADIUS Account Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS Accounts'),
                    ['controller' => 'Accounts', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS Checks'),
                    ['controller' => 'Radcheck', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS Replies'),
                    ['controller' => 'Radreply', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS User Groups'),
                    ['controller' => 'Radusergroup', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __d('radius', 'RADIUS Monitoring Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS Accountings'),
                    ['controller' => 'Radacct', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS Post Authentications'),
                    ['controller' => 'Radpostauth', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __d('radius', 'RADIUS Groups Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS Group Checks'),
                    ['controller' => 'Radgroupcheck', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS Group Replies'),
                    ['controller' => 'Radgroupreply', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>

        <div class="related">
            <h4><?= __d('radius', 'RADIUS System Related') ?></h4>
            <div>
                <?= $this->AuthLink->link(
                    __d('radius', 'List RADIUS NAS'),
                    ['controller' => 'Nas', 'action' => 'index'],
                    ['class' => 'side-nav-item']
                ) ?>
            </div>
        </div>
    </div>
</div>
