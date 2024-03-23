<?php
use Cake\Routing\Router;

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
            <td>
                <div
                    hx-get="<?= Router::url([
                        'prefix' => 'Api',
                        'controller' => 'IpNetworks',
                        'action' => 'ipAddressRanges',
                        'ip_network' => strtr($ipNetwork->ip_network, ['/' => '-mask-']),
                        '_ext' => 'ajax',
                    ]) ?>"
                    hx-trigger="load"><?= __('Loading...') ?></div>
            </td>
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
