<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Login $login
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var string $new_login
 * @var string $new_password
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Logins'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="logins form content">
            <?= $this->Form->create($login) ?>
            <fieldset>
                <legend><?= __('Add Login') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                }
                echo $this->Form->control('login', ['default' => $new_login]);
                echo $this->Form->control('password', ['type' => 'text', 'default' => $new_password]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
