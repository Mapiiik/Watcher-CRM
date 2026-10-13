<?php
/**
 * Whether tasks can be given to an account.
 *
 * @var \App\View\AppView $this
 * @var string $part
 * @var \App\Model\Entity\AppUser|null $user
 */
?>
<?php if ($part === 'heading') : ?>
    <th><?= $this->Paginator->sort('holds_tasks') ?></th>
<?php elseif ($part === 'cell' && $user !== null) : ?>
    <td><?= $user->holds_tasks ? __d('app_users', 'Yes') : __d('app_users', 'No') ?></td>
<?php elseif ($part === 'details' && $user !== null) : ?>
    <tr>
        <th><?= __d('app_users', 'Holds Tasks') ?></th>
        <td><?= $user->holds_tasks ? __d('app_users', 'Yes') : __d('app_users', 'No') ?></td>
    </tr>
<?php elseif ($part === 'form') : ?>
    <?= $this->Form->control('holds_tasks') ?>
<?php endif;
