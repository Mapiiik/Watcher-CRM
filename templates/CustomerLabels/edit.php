<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerLabel $customerLabel
 * @var string[]|\Cake\Collection\CollectionInterface $labels
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $customerLabel->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $customerLabel->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Customer Labels'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="customerLabels form content">
            <?= $this->Form->create($customerLabel) ?>
            <fieldset>
                <legend><?= __('Edit Customer Label') ?></legend>
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
