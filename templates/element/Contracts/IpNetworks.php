<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var string[]|\Cake\Collection\CollectionInterface $ip_network_types_of_use
 */
?>
<?php if (!empty($contract->ip_networks)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('IP Network') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('IP Address Range') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($contract->ip_networks as $ipNetwork) : ?>
        <tr style="<?= $ipNetwork->style ?>">
            <td><?= h($ipNetwork->ip_network) ?></td>
            <td><?= h($ip_network_types_of_use[$ipNetwork->type_of_use]) ?></td>
            <td><?= h($ipNetwork->note) ?></td>
            <td><?php
            if (isset($ipNetwork->ip_address_ranges)) {
                $range = $ipNetwork->ip_address_ranges->first();
                echo isset($range['access_point']['id']) ?
                    __('Access Point') . ': ' . $this->Html->link(
                        $range['access_point']['name'],
                        env('WATCHER_NMS_URL')
                            . '/access-points/view/' . $range['access_point']['id'],
                        ['target' => '_blank']
                    ) . '<br>' : '';
                echo isset($range['id']) ?
                    __('Range') . ': ' . $this->Html->link(
                        $range['name'],
                        env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
                        ['target' => '_blank']
                    ) . '<br>' : '';
                unset($range);
            }
            ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'IpNetworks', 'action' => 'view', $ipNetwork->id]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'IpNetworks', 'action' => 'edit', $ipNetwork->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'IpNetworks', 'action' => 'delete', $ipNetwork->id],
                    [
                        'confirm' => __(
                            'Are you sure you want to delete # {0}?',
                            $ipNetwork->ip_network
                        ),
                    ]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
