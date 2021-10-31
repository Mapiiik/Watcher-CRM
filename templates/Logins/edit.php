<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Login $login
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $login->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $login->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Logins'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="logins form content">
            <?= $this->Form->create($login) ?>
            <fieldset>
                <legend><?= __('Edit Login') ?></legend>
                <?php
                    if (!isset($customer_id)) echo $this->Form->control('customer_id', ['options' => $customers]);
                    echo $this->Form->control('login');
                    echo $this->Form->control('new_password', ['label' => __('New Password')]);
                    echo $this->Form->control('locked');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
