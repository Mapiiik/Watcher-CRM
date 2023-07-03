<?php
/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\RemovedIp> $removed_ip_addresses
 * @var \Cake\Collection\CollectionInterface|array<string> $ip_address_types_of_use
 * @var bool $contract_column
 */
?>
<?php if (!empty($removed_ip_addresses)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('IP Address') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('Removed') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($removed_ip_addresses as $removedIp) : ?>
        <tr style="<?= $removedIp->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $removedIp->__isset('contract') ?
                $this->Html->link(
                    $removedIp->contract->number,
                    ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]
                ) : '' ?></td>
            <?php endif; ?>
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
