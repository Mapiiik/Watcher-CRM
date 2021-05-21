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
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $radacct->radacctid],
                ['confirm' => __('Are you sure you want to delete # {0}?', $radacct->radacctid), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Radacct'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radacct form content">
            <?= $this->Form->create($radacct) ?>
            <fieldset>
                <legend><?= __('Edit Radacct') ?></legend>
                <?php
                    echo $this->Form->control('acctsessionid');
                    echo $this->Form->control('acctuniqueid');
                    echo $this->Form->control('username');
                    echo $this->Form->control('realm');
                    echo $this->Form->control('nasipaddress');
                    echo $this->Form->control('nasportid');
                    echo $this->Form->control('nasporttype');
                    echo $this->Form->control('acctstarttime');
                    echo $this->Form->control('acctstoptime');
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
                    echo $this->Form->control('acctstartdelay');
                    echo $this->Form->control('acctstopdelay');
                    echo $this->Form->control('groupname');
                    echo $this->Form->control('xascendsessionsvrkey');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
