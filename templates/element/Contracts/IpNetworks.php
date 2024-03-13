<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\IpNetwork> $ip_networks
 * @var bool $contract_column
 */
?>
<?php if (!empty($ip_networks)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('IP Network') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('IP Address Range') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($ip_networks as $ipNetwork) : ?>
        <tr style="<?= $ipNetwork->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $ipNetwork->__isset('contract') ?
                $this->Html->link(
                    $ipNetwork->contract->number ?? '--',
                    ['controller' => 'Contracts', 'action' => 'view', $ipNetwork->contract->id]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= h($ipNetwork->ip_network) ?></td>
            <td><?= h($ipNetwork->type_of_use->label()) ?></td>
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
