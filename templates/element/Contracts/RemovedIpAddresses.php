<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\RemovedIpAddress> $removed_ip_addresses
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
        <?php foreach ($removed_ip_addresses as $removedIpAddress) : ?>
        <tr style="<?= $removedIpAddress->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $removedIpAddress->__isset('contract') ?
                $this->Html->link(
                    $removedIpAddress->contract->number ?? '--',
                    ['controller' => 'Contracts', 'action' => 'view', $removedIpAddress->contract->id]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= h($removedIpAddress->ip_address) ?></td>
            <td><?= h($removedIpAddress->type_of_use->label()) ?></td>
            <td><?= h($removedIpAddress->note) ?></td>
            <td><?= h($removedIpAddress->removed) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'RemovedIpAddresses', 'action' => 'view', $removedIpAddress->id]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'RemovedIpAddresses', 'action' => 'edit', $removedIpAddress->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'RemovedIpAddresses', 'action' => 'delete', $removedIpAddress->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $removedIpAddress->id)]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
