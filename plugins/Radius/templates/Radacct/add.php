<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Radacct $radacct
 * @var \Cake\Collection\CollectionInterface|array<string> $accounts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Accountings'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radacct form content">
            <?= $this->Form->create($radacct) ?>
            <fieldset>
                <legend><?= __d('radius', 'Add RADIUS Accounting') ?></legend>
                <?php
                    echo $this->Form->control('acctsessionid');
                    echo $this->Form->control('acctuniqueid');
                    echo $this->Form->control('username', ['options' => $accounts, 'empty' => true]);
                    echo $this->Form->control('realm');
                    echo $this->Form->control('nasipaddress');
                    echo $this->Form->control('nasportid');
                    echo $this->Form->control('nasporttype');
                    echo $this->Form->control('acctstarttime');
                    echo $this->Form->control('acctupdatetime');
                    echo $this->Form->control('acctstoptime');
                    echo $this->Form->control('acctinterval');
                    echo $this->Form->control('acctsessiontime');
                    echo $this->Form->control('acctauthentic');
                    echo $this->Form->control('connectinfo_start');
                    echo $this->Form->control('connectinfo_stop');
                    echo $this->Form->control('acctinputoctets');
                    echo $this->Form->control('acctoutputoctets');
                    echo $this->Form->control('calledstationid');
                    echo $this->Form->control('callingstationid');
                    echo $this->Form->control('acctterminatecause');
                    echo $this->Form->control('servicetype');
                    echo $this->Form->control('framedprotocol');
                    echo $this->Form->control('framedipaddress');
                    echo $this->Form->control('framedipv6address');
                    echo $this->Form->control('framedipv6prefix');
                    echo $this->Form->control('framedinterfaceid');
                    echo $this->Form->control('delegatedipv6prefix');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
