<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Accounts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="accounts form content">
            <?= $this->Form->create() ?>
            <fieldset>
                <legend><?= __d('radius', 'Update Related Records') ?></legend>
                <?php
                echo $this->Form->control('state', [
                    'label' => __d('radius', 'State'),
                    'type' => 'select',
                    'options' => [
                        'all' => __d('radius', 'All'),
                        'active' => __d('radius', 'Active'),
                        'inactive' => __d('radius', 'Inactive'),
                    ],
                ]);
                echo $this->Form->control('radcheck', [
                    'label' => __d('radius', 'Update RADIUS Checks'),
                    'type' => 'checkbox',
                    'default' => true,
                ]);
                echo $this->Form->control('radreply', [
                    'label' => __d('radius', 'Update RADIUS Replies'),
                    'type' => 'checkbox',
                    'default' => true,
                ]);
                echo $this->Form->control('radusergroup', [
                    'label' => __d('radius', 'Update RADIUS User Groups'),
                    'type' => 'checkbox',
                    'default' => true,
                ]);
                echo $this->Form->control('reconnect_modified_accounts', [
                    'label' => __d('radius', 'Reconnect Modified RADIUS Accounts'),
                    'type' => 'checkbox',
                    'default' => true,
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(
                __d('radius', 'Submit'),
                [
                    'confirm' => __d('radius', 'Are you sure you want to update related records for all accounts?'),
                ]
            ) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
