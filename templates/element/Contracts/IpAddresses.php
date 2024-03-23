<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\IpAddress> $ip_addresses
 * @var bool $contract_column
 */

use Cake\Routing\Router;

?>
<?php if (!empty($ip_addresses)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('IP Address') ?></th>
            <th><?= __('Type Of Use') ?></th>
            <th><?= __('Note') ?></th>
            <th><?= __('Status') ?></th>
            <th><?= __('Device') ?></th>
            <th><?= __('IP Address Range') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($ip_addresses as $ipAddress) : ?>
        <tr style="<?= $ipAddress->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $ipAddress->__isset('contract') ?
                $this->Html->link(
                    $ipAddress->contract->number ?? '--',
                    ['controller' => 'Contracts', 'action' => 'view', $ipAddress->contract->id]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= h($ipAddress->ip_address) ?></td>
            <td><?= h($ipAddress->type_of_use->label()) ?></td>
            <td><?= h($ipAddress->note) ?></td>
            <td class="to-center"><?=
                $this->Html->image('ping/status.png.php?host=' . h($ipAddress->ip_address), [
                    'class' => 'ping-status',
                    'onclick' => 'this.src = this.src',
                ]) ?></td>
            <td>
                <div
                    hx-get="<?= Router::url([
                        'prefix' => 'Api',
                        'controller' => 'IpAddresses',
                        'action' => 'routerosDevices',
                        'ip_address' => $ipAddress->ip_address,
                        '_ext' => 'ajax',
                    ]) ?>"
                    hx-trigger="load"><?= __('Loading...') ?></div>
            </td>
            <td>
                <div
                    hx-get="<?= Router::url([
                        'prefix' => 'Api',
                        'controller' => 'IpAddresses',
                        'action' => 'ipAddressRanges',
                        'ip_address' => $ipAddress->ip_address,
                        '_ext' => 'ajax',
                    ]) ?>"
                    hx-trigger="load"><?= __('Loading...') ?></div>
            </td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'IpAddresses', 'action' => 'view', $ipAddress->id]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'IpAddresses', 'action' => 'edit', $ipAddress->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'IpAddresses', 'action' => 'delete', $ipAddress->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $ipAddress->ip_address)]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
