<?php
/**
 * How an account signs in, when and from where it last did, and somebody last failed to: rows of
 * a table.
 *
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 */
?>
<tr>
    <th><?= __d('access', 'Password Sign-In') ?></th>
    <td><?= $user->hasPasswordSignIn() ? __d('access', 'On') : __d('access', 'Off') ?></td>
</tr>
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
