<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerMessage $customerMessage
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $customerMessage->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $customerMessage->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(__('List Customer Messages'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerMessages form content">
            <?= $this->Form->create($customerMessage) ?>
            <fieldset>
                <legend><?= __('Edit Customer Message') ?></legend>
                <?php
                    echo $this->Form->control('customer_id', ['options' => $customers]);
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
