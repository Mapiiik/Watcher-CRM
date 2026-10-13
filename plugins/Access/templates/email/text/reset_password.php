<?php
/**
 * @var \Cake\View\View $this
 * @var string $username
 * @var string $firstName
 * @var string $url
 */
?>
<?= $firstName !== '' ? __d('access', 'Hello {0},', $firstName) : __d('access', 'Hello,') ?>


<?= __d('access', 'somebody asked for a new password for the account {0}. If it was you, set it here:', $username) ?>


<?= $url ?>


<?= __d(
    'access',
    'The link works for an hour, and once. If it was not you, there is nothing to do: the password stays as it is.',
);
