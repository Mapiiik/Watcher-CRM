<?php
/**
 * The customer an account belongs to, as a dealer's accounts do.
 *
 * @var \App\View\AppView $this
 * @var string $part
 * @var \App\Model\Entity\AppUser|null $user
 */
?>
<?php if ($part === 'heading') : ?>
    <th><?= $this->Paginator->sort('customer_id') ?></th>
<?php elseif ($part === 'cell' && $user !== null) : ?>
    <td><?= h($user->customer_id) ?></td>
<?php elseif ($part === 'details' && $user !== null) : ?>
    <tr>
        <th><?= __d('app_users', 'Customer Id') ?></th>
        <td><?= h($user->customer_id) ?></td>
    </tr>
<?php elseif ($part === 'form') : ?>
    <?= $this->Form->control('customer_id', ['type' => 'text']) ?>
<?php endif;
