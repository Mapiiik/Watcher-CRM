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
            <?= $this->Html->link(__('List Logins'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="logins form content">
            <?= $this->Form->create($login) ?>
            <fieldset>
                <legend><?= __('Add Login') ?></legend>
                <?php
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                    echo $this->Form->control('login');
                    echo $this->Form->control('password');
                    echo $this->Form->control('rights');
                    echo $this->Form->control('locked');
                    echo $this->Form->control('last_granted');
                    echo $this->Form->control('last_granted_ip');
                    echo $this->Form->control('last_denied');
                    echo $this->Form->control('last_denied_ip');
                    echo $this->Form->control('modified_by');
                    echo $this->Form->control('created_by');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
