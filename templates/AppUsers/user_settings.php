<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppUser $user
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_users', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_users', 'List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __d('app_users', 'User Profile'),
                ['action' => 'profile'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __d('app_users', 'Edit User Settings') ?></legend>
                <?php
                echo $this->Form->control('user_settings.language', [
                    'label' => __d('app_users', 'Language'),
                    'options' => [
                        'cs_CZ' => 'Čeština',
                        'en_US' => 'English',
                    ],
                ]);
                echo $this->Form->control('user_settings.number_of_rows_per_page', [
                    'label' => __d('app_users', 'Number of Rows per Page'),
                    'options' => [
                        20 => 20,
                        50 => 50,
                        100 => 100,
                        500 => 500,
                        1000 => 1000,
                        5000 => 5000,
                        10000 => 10000,
                    ],
                ]);
                echo $this->Form->control('user_settings.theme', [
                    'label' => __d('app_users', 'Theme'),
                    'options' => [
                        'default' => __('Default'),
                        'contrast' => __('Contrast'),
                        'legacy' => __('Legacy'),
                        'dark' => __('Dark') . ' (dev)',
                    ],
                ]);
                echo $this->Form->control('user_settings.customers.advanced_search', [
                    'label' => __d('app_users', 'Customers') . ' - ' . __d('app_users', 'Advanced Search by Default'),
                    'type' => 'checkbox',
                ]);
                echo $this->Form->control('user_settings.tasks.all_by_default', [
                    'label' =>
                        __d('app_users', 'Tasks')
                        . ' - '
                        . __d('app_users', 'Show Tasks for All Users by Default')
                    ,
                    'type' => 'checkbox',
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('app_users', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
