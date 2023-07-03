<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerLabel $customerLabel
 * @var \Cake\Collection\CollectionInterface|array<string> $labels
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Customer Labels'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerLabels form content">
            <?= $this->Form->create($customerLabel) ?>
            <fieldset>
                <legend><?= __('Add Customer Label') ?></legend>
                <?php
                echo $this->Form->control('label_id', ['options' => $labels]);
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                }
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
