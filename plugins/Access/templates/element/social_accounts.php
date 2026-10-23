<?php
/**
 * The identities at other providers the account signs in with, for its owner to link or unlink.
 *
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 */

use Access\Social\Providers;

$providers = Providers::names();
if ($providers === []) {
    return;
}
$linked = array_map(fn($link) => $link->provider, $user->social_accounts ?? []);
?>
<div class="related">
<?php foreach (array_diff($providers, $linked) as $provider) : ?>
    <?= $this->Html->link(
        __d('access', 'Link an account at {0}', Providers::label($provider)),
        ['action' => 'linkSocial', 'provider' => $provider],
        ['class' => 'button button-small float-right'],
    ) ?>
<?php endforeach; ?>
<h4><?= __d('access', 'Social Accounts') ?></h4>
<?php if (!empty($user->social_accounts)) : ?>
    <table>
        <tr>
            <th><?= __d('access', 'Provider') ?></th>
            <th><?= __d('access', 'Username') ?></th>
            <th class="actions"><?= __d('access', 'Actions') ?></th>
        </tr>
        <?php foreach ($user->social_accounts as $link) : ?>
            <tr>
                <td><?= h(Providers::label($link->provider)) ?></td>
                <td><?= h($link->username) ?></td>
                <td class="actions">
                    <?= $this->Form->postLink(
                        __d('access', 'Unlink'),
                        ['action' => 'unlinkSocial', $link->id],
                        ['confirm' => __d('access', 'Are you sure you want to unlink {0}?', $link->username)],
                    ) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>
