<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var string[]|\Cake\Collection\CollectionInterface $ip_address_types_of_use
 */
?>
<?php if (!empty($contract->ips)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('IP Address') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('Status') ?></th>
            <th><?= __('Device') ?></th>
            <th><?= __('IP Address Range') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($contract->ips as $ip) : ?>
        <tr style="<?= $ip->style ?>">
            <td><?= h($ip->ip) ?></td>
            <td><?= h($ip_address_types_of_use[$ip->type_of_use]) ?></td>
            <td><?= h($ip->note) ?></td>
            <td class="to-center"><?=
                $this->Html->image('ping/status.png.php?host=' . h($ip->ip), [
                    'class' => 'ping-status',
                    'onclick' => 'this.src = this.src',
                ]) ?></td>
            <td><?php
            if (isset($ip->routeros_devices)) {
                $device = $ip->routeros_devices->first();
                echo isset($device['id']) ?
                    $this->Html->link(
                        $device['system_description'],
                        env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
                        ['target' => '_blank']
                    ) . '<br>' : '';
                unset($device);
            }
            ?></td>
            <td><?php
            if (isset($ip->ip_address_ranges)) {
                $range = $ip->ip_address_ranges->first();
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
                    ['controller' => 'Ips', 'action' => 'view', $ip->id]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'Ips', 'action' => 'edit', $ip->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'Ips', 'action' => 'delete', $ip->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip)]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
