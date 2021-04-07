<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $contract->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="contracts form content">
            <?= $this->Form->create($contract) ?>
            <fieldset>
                <legend><?= __('Edit Contract') ?></legend>
                <?php
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                    echo $this->Form->control('installation_address_id', ['options' => $installationAddresses, 'empty' => true]);
                    echo $this->Form->control('number');
                    echo $this->Form->control('service_type_id', ['options' => $serviceTypes]);
                    echo $this->Form->control('created_by');
                    echo $this->Form->control('modified_by');
                    echo $this->Form->control('note');
                    echo $this->Form->control('obligation_until', ['empty' => true]);
                    echo $this->Form->control('vip');
                    echo $this->Form->control('installation_technician_id', ['options' => $installationTechnicians, 'empty' => true]);
                    echo $this->Form->control('brokerage_id', ['options' => $brokerages, 'empty' => true]);
                    echo $this->Form->control('installation_date', ['empty' => true]);
                    echo $this->Form->control('access_description');
                    echo $this->Form->control('valid_from', ['empty' => true]);
                    echo $this->Form->control('valid_until', ['empty' => true]);
                    echo $this->Form->control('conclusion_date', ['empty' => true]);
                    echo $this->Form->control('number_of_amendments');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
