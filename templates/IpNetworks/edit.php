<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpNetwork $ipNetwork
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 * @var string[]|\Cake\Collection\CollectionInterface $contracts
 * @var string[]|\Cake\Collection\CollectionInterface $types_of_use
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $ipNetwork->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ipNetwork->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('List IP Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ipNetworks form content">
            <?= $this->Form->create($ipNetwork) ?>
            <fieldset>
                <legend><?= __('Edit IP Network') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts, 'empty' => true]);
                }
                echo $this->Form->control('ip_network', ['label' => __('IP Network'), 'disabled' => true]);
                echo $this->Form->control('type_of_use', ['options' => $types_of_use]);
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
