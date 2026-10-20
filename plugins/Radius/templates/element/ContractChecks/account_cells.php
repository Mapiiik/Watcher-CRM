<?php
/**
 * The account a finding is about and the groups it is in, as cells of a check's listing.
 *
 * @var \App\View\AppView $this
 * @var \Radius\Check\AccountFinding $finding
 */
?>
<td>
    <?= $this->AuthLink->link($finding->account->username, [
        'plugin' => 'Radius',
        'controller' => 'Accounts',
        'action' => 'view',
        $finding->account->id,
    ]) ?>
</td>
<td><?= implode('<br>', array_map(h(...), $finding->groupnames())) ?></td>
