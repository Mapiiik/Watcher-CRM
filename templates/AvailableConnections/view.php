<?php
use App\Model\Enum\AvailableConnectionOrigin;

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AvailableConnection $availableConnection
 * @var array<string, string> $accessPoints
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Available Connection'),
                ['action' => 'edit', $availableConnection->id],
                ['class' => 'side-nav-item win-link'],
            ) ?>
            <?php if ($availableConnection->retired === null) : ?>
                <?= $this->AuthLink->postLink(
                    __('Retire Available Connection'),
                    ['action' => 'retire', $availableConnection->id],
                    [
                        'confirm' => __('The connection will no longer be reported from today. Retire it?'),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?php endif; ?>
            <?= $this->AuthLink->postLink(
                __('Delete Available Connection'),
                ['action' => 'delete', $availableConnection->id],
                [
                    'confirm' => $availableConnection->origin === AvailableConnectionOrigin::Contract
                        ? __(
                            'The synchronisation will record it again while its contract is there.'
                            . ' Retiring it keeps it away. Delete anyway?',
                        )
                        : __('Are you sure you want to delete # {0}?', $availableConnection->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Available Connections'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Available Connection'),
                ['action' => 'add'],
                ['class' => 'side-nav-item win-link'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="availableConnections view content">
            <?= $this->record(__('Available Connection'), (string)$availableConnection->address_label) ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Address') ?></th>
                            <td><?= h($availableConnection->address_label) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Address Point') ?></th>
                            <td><?= h($availableConnection->address_registry_reference)
                                . ' (' . h(strtoupper($availableConnection->address_registry_source)) . ')' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Access Technology') ?></th>
                            <td><?= h($availableConnection->access_technology->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Maximum Download Speed (kbps)') ?></th>
                            <td><?= $this->Number->format($availableConnection->speed_down_max) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Maximum Upload Speed (kbps)') ?></th>
                            <td><?= $this->Number->format($availableConnection->speed_up_max) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Access Point') ?></th>
                            <td><?= $availableConnection->access_point_id === null ? '' : h(
                                $accessPoints[$availableConnection->access_point_id]
                                    ?? $availableConnection->access_point_id,
                            ) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Origin') ?></th>
                            <td><?= h($availableConnection->origin->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $availableConnection->contract !== null ? $this->Html->link(
                                (string)$availableConnection->contract->number,
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $availableConnection->contract->id,
                                    'customer_id' => $availableConnection->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Retired') ?></th>
                            <td><?= h($availableConnection->retired) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $availableConnection]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($availableConnection->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
