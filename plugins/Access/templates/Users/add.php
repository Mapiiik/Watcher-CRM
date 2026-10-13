<?php
/**
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('access', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('access', 'List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <?= $this->legend(__d('access', 'Add User')) ?>
                <?php
                    echo $this->Form->control('username');
                    echo $this->Form->control('email');
                    echo $this->Form->control('password', ['value' => '', 'autocomplete' => 'new-password']);
                    echo $this->Form->control('first_name');
                    echo $this->Form->control('last_name');
                    echo $this->Form->control('role', ['options' => $user->getRoleOptions()]);
                    echo $this->Form->control('active');
                    echo $this->element('Access.account_fields', ['part' => 'form', 'user' => $user]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('access', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
