<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;
?>
<div class="users form content">
    <?= $this->Flash->render('auth') ?>
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __d('app_users', 'Please enter your username and password') ?></legend>
        <?= $this->Form->control('username', ['label' => __d('app_users', 'Username'), 'required' => true]) ?>
        <?= $this->Form->control('password', ['label' => __d('app_users', 'Password'), 'required' => true]) ?>
        <?php
        if (Configure::read('Users.reCaptcha.login')) {
            echo $this->User->addReCaptcha();
        }
        if (Configure::read('Users.RememberMe.active')) {
            echo $this->Form->control(Configure::read('Users.Key.Data.rememberMe'), [
                'type' => 'checkbox',
                'label' => __d('app_users', 'Remember me'),
                'checked' => Configure::read('Users.RememberMe.checked'),
            ]);
        }
        ?>
        <?php
        $registrationActive = Configure::read('Users.Registration.active');
        if ($registrationActive) {
            echo $this->Html->link(__d('app_users', 'Register'), ['action' => 'register']);
        }
        if (Configure::read('Users.Email.required')) {
            if ($registrationActive) {
                echo ' | ';
            }
            echo $this->Html->link(__d('app_users', 'Reset Password'), ['action' => 'requestResetPassword']);
        }
        ?>
    </fieldset>
    <?= implode(' ', $this->User->socialLoginList()); ?>
    <?= $this->Form->button(__d('app_users', 'Login')); ?>
    <?= $this->Form->end() ?>
</div>
