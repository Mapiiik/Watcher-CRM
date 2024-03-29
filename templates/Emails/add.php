<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Email $email
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Emails'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="emails form content">
            <?= $this->Form->create($email) ?>
            <fieldset>
                <legend><?= __('Add Email') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                }
                echo $this->Form->control('email');
                echo $this->Form->control('use_for_billing');
                echo $this->Form->control('use_for_outages');
                echo $this->Form->control('use_for_commercial');
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
