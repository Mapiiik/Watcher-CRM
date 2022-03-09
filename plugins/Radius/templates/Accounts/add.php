<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $account
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'List Accounts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="accounts form content">
            <?= $this->Form->create($account) ?>
            <fieldset>
                <legend><?= __d('radius', 'Add Account') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', [
                        'label' => __d('radius', 'Customer'),
                        'options' => $customers,
                    ]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', [
                        'label' => __d('radius', 'Contract'),
                        'options' => $contracts,
                    ]);
                }
                echo $this->Form->control('username', [
                    'label' => __d('radius', 'Username'),
                    'type' => 'text',
                    'default' => $new_username,
                ]);
                echo $this->Form->control('password', [
                    'label' => __d('radius', 'Password'),
                    'type' => 'text',
                    'default' => $new_password,
                ]);
                echo $this->Form->control('active', ['label' => __d('radius', 'Active')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
