<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var string[]|\Cake\Collection\CollectionInterface $ip_address_types_of_use
 */
?>
<?php if (!empty($contract->removed_ips)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('IP Address') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('Removed') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($contract->removed_ips as $removedIp) : ?>
        <tr style="<?= $removedIp->style ?>">
            <td><?= h($removedIp->ip) ?></td>
            <td><?= h($ip_address_types_of_use[$removedIp->type_of_use]) ?></td>
            <td><?= h($removedIp->note) ?></td>
            <td><?= h($removedIp->removed) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'RemovedIps', 'action' => 'view', $removedIp->id]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'RemovedIps', 'action' => 'edit', $removedIp->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'RemovedIps', 'action' => 'delete', $removedIp->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
