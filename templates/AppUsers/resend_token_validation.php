<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppUser $user
 */

?>
<div class="users form content">
    <?= $this->Form->create($user); ?>
    <fieldset>
        <?= $this->legend(__d('app_users', 'Resend Validation email')) ?>
        <?php
        echo $this->Form->control('reference', ['label' => __d('app_users', 'Email or username')]);
        ?>
    </fieldset>
    <?= $this->Form->button(__d('app_users', 'Submit')) ?>
    <?= $this->Form->end() ?>
</div>
