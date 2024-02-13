<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerMessage $customerMessage
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Customer Messages'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerMessages form content">
            <?= $this->Form->create($customerMessage) ?>
            <fieldset>
                <legend><?= __('Add Customer Message') ?></legend>
                <?php
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                    echo $this->Form->control('type');
                    echo $this->Form->control('direction');
                    echo $this->Form->control('recipients');
                    echo $this->Form->control('subject');
                    echo $this->Form->control('body');
                    echo $this->Form->control('body_format');
                    echo $this->Form->control('delivery_status');
                    echo $this->Form->control('processed');
                    echo $this->Form->control('identifier');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
