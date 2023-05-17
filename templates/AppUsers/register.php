<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;
?>
<div class="users form content">
    <?= $this->Form->create($user); ?>
    <fieldset>
        <legend><?= __d('app_users', 'Add User') ?></legend>
        <?php
        echo $this->Form->control('username', ['label' => __d('app_users', 'Username')]);
        echo $this->Form->control('email', ['label' => __d('app_users', 'Email')]);
        echo $this->Form->control('password', ['label' => __d('app_users', 'Password')]);
        echo $this->Form->control('password_confirm', [
            'required' => true,
            'type' => 'password',
            'label' => __d('app_users', 'Confirm password'),
        ]);
        echo $this->Form->control('first_name', ['label' => __d('app_users', 'First name')]);
        echo $this->Form->control('last_name', ['label' => __d('app_users', 'Last name')]);
        if (Configure::read('Users.Tos.required')) {
            echo $this->Form->control('tos', [
                'type' => 'checkbox',
                'label' => __d('app_users', 'Accept TOS conditions?'),
                'required' => true,
            ]);
        }
        if (Configure::read('Users.reCaptcha.registration')) {
            echo $this->User->addReCaptcha();
        }
        ?>
    </fieldset>
    <?= $this->Form->button(__d('app_users', 'Submit')) ?>
    <?= $this->Form->end() ?>
</div>
