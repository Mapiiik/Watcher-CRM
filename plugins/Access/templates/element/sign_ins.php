<?php
/**
 * When and from where an account last signed in, and somebody last failed to: rows of a table.
 *
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 */
?>
<tr>
    <th><?= __d('access', 'Last Granted') ?></th>
    <td><?= h($user->last_granted) ?></td>
</tr>
<tr>
    <th><?= __d('access', 'Last Granted IP Address') ?></th>
    <td><?= h($user->last_granted_ip) ?></td>
</tr>
<tr>
    <th><?= __d('access', 'Last Denied') ?></th>
    <td><?= h($user->last_denied) ?></td>
</tr>
<tr>
    <th><?= __d('access', 'Last Denied IP Address') ?></th>
    <td><?= h($user->last_denied_ip) ?></td>
</tr>
