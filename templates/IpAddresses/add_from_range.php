<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpAddress $ipAddress
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $customers
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List IP Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ipAddresses form content">
            <?= $this->Form->create($ipAddress) ?>
            <fieldset>
                <legend><?= __('Add IP Address From Range') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', [
                        'options' => $customers,
                        'empty' => true,
                        'onchange' => $this::REFRESH_ON_CHANGE,
                    ]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', [
                        'options' => $contracts,
                        'empty' => true,
                        'onchange' => $this::REFRESH_ON_CHANGE,
                    ]);
                }
                echo $this->Form->control('type_of_use', [
                    'onchange' => $this::REFRESH_ON_CHANGE,
                ]);
                echo $this->Form->control('ip_address_range', [
                    'label' => __('IP Address Range'),
                    'empty' => true,
                    'onchange' => $this::REFRESH_ON_CHANGE,
                ]);
                $this->Form->unlockField('refresh'); //disable form security check

                echo $this->Form->control('ip_address', [
                    'label' => __('IP Address'),
                    'empty' => true,
                ]);
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
