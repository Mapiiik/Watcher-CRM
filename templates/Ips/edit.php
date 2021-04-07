<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ip $ip
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $ip->ip],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="ips form content">
            <?= $this->Form->create($ip) ?>
            <fieldset>
                <legend><?= __('Edit Ip') ?></legend>
                <?php
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                    echo $this->Form->control('queue_id', ['options' => $queues]);
                    echo $this->Form->control('device_id', ['options' => $devices]);
                    echo $this->Form->control('mac');
                    echo $this->Form->control('comment');
                    echo $this->Form->control('cost');
                    echo $this->Form->control('dealer_id');
                    echo $this->Form->control('installation_date', ['empty' => true]);
                    echo $this->Form->control('brokerage_id', ['options' => $brokerages, 'empty' => true]);
                    echo $this->Form->control('billing_from', ['empty' => true]);
                    echo $this->Form->control('note');
                    echo $this->Form->control('vip');
                    echo $this->Form->control('bond', ['empty' => true]);
                    echo $this->Form->control('active_until', ['empty' => true]);
                    echo $this->Form->control('router_id', ['options' => $routers, 'empty' => true]);
                    echo $this->Form->control('access_description');
                    echo $this->Form->control('contract_id', ['options' => $contracts]);
                    echo $this->Form->control('id');
                    echo $this->Form->control('created_by');
                    echo $this->Form->control('modified_by');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
