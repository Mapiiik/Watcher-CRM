<?php
/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\RemovedIpNetwork> $removed_ip_networks
 * @var bool $contract_column
 */
?>
<?php if (!empty($removed_ip_networks)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('IP Network') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('Removed') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($removed_ip_networks as $removedIpNetwork) : ?>
        <tr style="<?= $removedIpNetwork->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $removedIpNetwork->__isset('contract') ?
                $this->Html->link(
                    $removedIpNetwork->contract->number,
                    [
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $removedIpNetwork->contract->id,
                    ]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= h($removedIpNetwork->ip_network) ?></td>
            <td><?= h($removedIpNetwork->getTypeOfUseName()) ?></td>
            <td><?= h($removedIpNetwork->note) ?></td>
            <td><?= h($removedIpNetwork->removed) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    [
                        'controller' => 'RemovedIpNetworks',
                        'action' => 'view',
                        $removedIpNetwork->id,
                    ]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    [
                        'controller' => 'RemovedIpNetworks',
                        'action' => 'edit',
                        $removedIpNetwork->id,
                    ],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    [
                        'controller' => 'RemovedIpNetworks',
                        'action' => 'delete',
                        $removedIpNetwork->id,
                    ],
                    [
                        'confirm' => __(
                            'Are you sure you want to delete # {0}?',
                            $removedIpNetwork->id
                        ),
                    ]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
