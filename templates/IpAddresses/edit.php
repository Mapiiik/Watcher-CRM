<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpAddress $ipAddress
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $ipAddress->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ipAddress->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List IP Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ipAddresses form content">
            <?= $this->Form->create($ipAddress) ?>
            <fieldset>
                <legend><?= __('Edit IP Address') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts]);
                }
                echo $this->Form->control('ip_address', ['label' => __('IP Address'), 'disabled' => true]);
                echo $this->Form->control('type_of_use', ['options' => $ipAddress->getTypeOfUseOptions()]);
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
