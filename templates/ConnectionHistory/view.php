<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ConnectionHistory $connectionHistory
 * @var iterable<\App\Model\Entity\ConnectionHistory> $relatedStations
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Connection History'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="connectionHistory view content">
            <?php
            // which interval is open, and whose: the period first, because that
            // is what tells one interval of an account from the next
            ?>
            <h3><?=
                $this->element('ConnectionHistory/first_seen', ['interval' => $connectionHistory])
                    . ' &ndash; ' . h($connectionHistory->last_seen)
                    . ' &middot; ' . h($connectionHistory->source->referenceLabel())
                    . ': ' . h($connectionHistory->source_reference)
            ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $connectionHistory->hasValue('customer') ? $this->Html->link(
                                $connectionHistory->customer->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $connectionHistory->customer->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $connectionHistory->hasValue('contract') ? $this->Html->link(
                                $connectionHistory->contract->number ?? '--',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $connectionHistory->contract->id,
                                    'customer_id' => $connectionHistory->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Source Reference') ?></th>
                            <td><?= $this->element('ConnectionHistory/source_reference', [
                                'interval' => $connectionHistory,
                            ]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('From') ?></th>
                            <td><?= $this->element('ConnectionHistory/first_seen', [
                                'interval' => $connectionHistory,
                            ]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Accuracy of the start') ?></th>
                            <td><?= h($connectionHistory->first_seen_source->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Until') ?></th>
                            <td><?= h($connectionHistory->last_seen) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Access Point') ?></th>
                            <td><?= $this->element('ConnectionHistory/access_point', [
                                'interval' => $connectionHistory,
                            ]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('RouterOS Device') ?></th>
                            <td><?= $this->element('ConnectionHistory/routeros_device', [
                                'interval' => $connectionHistory,
                            ]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('NAS IP Address') ?></th>
                            <td><?= h($connectionHistory->nas_ip_address) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('NAS Port ID') ?></th>
                            <td><?= h($connectionHistory->nas_port_id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Calling Station ID') ?></th>
                            <td><?= h($connectionHistory->station_id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Called Station ID') ?></th>
                            <td><?= h($connectionHistory->called_station_id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Framed IP Address') ?></th>
                            <td><?= h($connectionHistory->ip_address) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Framed IPv6 Prefix') ?></th>
                            <td><?= h($connectionHistory->ipv6_prefix) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $connectionHistory]) ?>
                </div>
            </div>
            <?php if (!empty($relatedStations)) : ?>
            <div class="related">
                <h4><?= __('The same station elsewhere') ?></h4>
                <p><?=
                    __(
                        'Other periods recorded for station {0}. The same station under more than'
                            . ' one account is usually either a mistake in the configuration or'
                            . ' equipment that has moved.',
                        h($connectionHistory->station_id),
                    ) ?></p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= __('From') ?></th>
                                <th><?= __('Until') ?></th>
                                <th><?= __('Customer') ?></th>
                                <th><?= __('Source Reference') ?></th>
                                <th><?= __('Access Point') ?></th>
                                <th><?= __('RouterOS Device') ?></th>
                                <th><?= __('NAS IP Address') ?></th>
                                <th><?= __('NAS Port ID') ?></th>
                                <th class="actions"><?= __('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatedStations as $interval) : ?>
                            <tr>
                                <td><?= $this->element('ConnectionHistory/first_seen', [
                                    'interval' => $interval,
                                ]) ?></td>
                                <td><?= h($interval->last_seen) ?></td>
                                <td><?=
                                    $interval->hasValue('customer') ? $this->Html->link(
                                        $interval->customer->name,
                                        [
                                            'controller' => 'Customers',
                                            'action' => 'view',
                                            $interval->customer->id,
                                        ],
                                    ) : '' ?></td>
                                <td><?=
                                    $this->element('ConnectionHistory/source_reference', [
                                        'interval' => $interval,
                                    ]) ?></td>
                                <td><?=
                                    $this->element('ConnectionHistory/access_point', [
                                        'interval' => $interval,
                                    ]) ?></td>
                                <td><?=
                                    $this->element('ConnectionHistory/routeros_device', [
                                        'interval' => $interval,
                                    ]) ?></td>
                                <td><?= h($interval->nas_ip_address) ?></td>
                                <td><?= h($interval->nas_port_id) ?></td>
                                <td class="actions">
                                    <?= $this->AuthLink->link(
                                        __('View'),
                                        ['action' => 'view', $interval->id],
                                    ) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
