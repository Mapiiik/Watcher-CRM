<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var string[]|\Cake\Collection\CollectionInterface $ip_network_types_of_use
 */
?>
<?php if (!empty($contract->removed_ip_networks)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('IP Network') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('Removed') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($contract->removed_ip_networks as $removedIpNetork) : ?>
        <tr style="<?= $removedIpNetork->style ?>">
            <td><?= h($removedIpNetork->ip_network) ?></td>
            <td><?= h($ip_network_types_of_use[$removedIpNetork->type_of_use]) ?></td>
            <td><?= h($removedIpNetork->note) ?></td>
            <td><?= h($removedIpNetork->removed) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    [
                        'controller' => 'RemovedIpNetworks',
                        'action' => 'view',
                        $removedIpNetork->id,
                    ]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    [
                        'controller' => 'RemovedIpNetworks',
                        'action' => 'edit',
                        $removedIpNetork->id,
                    ],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    [
                        'controller' => 'RemovedIpNetworks',
                        'action' => 'delete',
                        $removedIpNetork->id,
                    ],
                    [
                        'confirm' => __(
                            'Are you sure you want to delete # {0}?',
                            $removedIpNetork->id
                        ),
                    ]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
