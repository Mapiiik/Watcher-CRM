<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppUser $user
 * @var string $tableAlias
 */

$user = ${$tableAlias};
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_users', 'Actions') ?></h4>
            <?= $this->Form->postLink(
                __d('app_users', 'Delete'),
                ['action' => 'delete', $user->id],
                [
                    'confirm' => __d('app_users', 'Are you sure you want to delete # {0}?', $user->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('app_users', 'List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __d('app_users', 'Edit User') ?></legend>
                <div class="row">
                    <div class="column">
                    <?php
                        echo $this->Form->control('username');
                        echo $this->Form->control('email');
                        echo $this->Form->control('first_name');
                        echo $this->Form->control('last_name');
                        echo $this->Form->control('role', ['options' => $user->getRoleOptions()]);
                        echo $this->Form->control('customer_id', ['type' => 'text']);
                        echo $this->Form->control('active');
                    ?>
                    </div>
                    <div class="column">
                    <?php
                        echo $this->Form->control('api_token');
                        echo $this->Form->control('token');
                        echo $this->Form->control('token_expires');
                        echo $this->Form->control('activation_date');
                        echo $this->Form->control('tos_date');
                        echo $this->Form->control('secret');
                        echo $this->Form->control('secret_verified');
                        echo $this->Form->control('additional_data');
                    ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__d('app_users', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
