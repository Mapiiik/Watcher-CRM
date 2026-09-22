<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ConnectionProfile $connectionProfile
 */
$derived = __('(derived)');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Connection Profile'),
                ['action' => 'edit', $connectionProfile->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Connection Profile'),
                ['action' => 'delete', $connectionProfile->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $connectionProfile->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Connection Profiles'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Connection Profile'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="connectionProfiles view content">
            <?= $this->record(__('Connection Profile'), (string)$connectionProfile->name) ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($connectionProfile->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('RADIUS Group') ?></th>
                            <td><?= h($connectionProfile->radius_group) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('FUP Limit') ?></th>
                            <td><?= $connectionProfile->fup_limit === null ?
                                '' : $this->Number->format($connectionProfile->fup_limit) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Data Limit') ?></th>
                            <td><?= $connectionProfile->data_limit === null ?
                                '' : $this->Number->format($connectionProfile->data_limit) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Overlimit Fragment') ?></th>
                            <td><?= $connectionProfile->overlimit_fragment === null ?
                                '' : $this->Number->format($connectionProfile->overlimit_fragment) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Overlimit Cost') ?></th>
                            <td><?= $connectionProfile->overlimit_cost === null ?
                                '' : $this->Number->currency($connectionProfile->overlimit_cost) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Down') ?></th>
                            <td><?= $connectionProfile->speed_down === null ?
                                '' : $this->Number->format($connectionProfile->speed_down) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Up') ?></th>
                            <td><?= $connectionProfile->speed_up === null ?
                                '' : $this->Number->format($connectionProfile->speed_up) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Down Commonly Available') ?></th>
                            <td><?=
                                $connectionProfile->getSpeedDownCommon() === null ? '' :
                                    $this->Number->format($connectionProfile->getSpeedDownCommon())
                                    . ($connectionProfile->speed_down_common === null ? ' ' . $derived : '')
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Up Commonly Available') ?></th>
                            <td><?=
                                $connectionProfile->getSpeedUpCommon() === null ? '' :
                                    $this->Number->format($connectionProfile->getSpeedUpCommon())
                                    . ($connectionProfile->speed_up_common === null ? ' ' . $derived : '')
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Down Minimum') ?></th>
                            <td><?=
                                $connectionProfile->getSpeedDownMinimum() === null ? '' :
                                    $this->Number->format($connectionProfile->getSpeedDownMinimum())
                                    . ($connectionProfile->speed_down_minimum === null ? ' ' . $derived : '')
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Speed Up Minimum') ?></th>
                            <td><?=
                                $connectionProfile->getSpeedUpMinimum() === null ? '' :
                                    $this->Number->format($connectionProfile->getSpeedUpMinimum())
                                    . ($connectionProfile->speed_up_minimum === null ? ' ' . $derived : '')
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Cto Category') ?></th>
                            <td><?= h($connectionProfile->cto_category) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $connectionProfile]) ?>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Services') ?></h4>
                <?php if (!empty($connectionProfile->services)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Service Type') ?></th>
                            <th><?= __('Currently Offered') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($connectionProfile->services as $service) : ?>
                        <tr>
                            <td><?= h($service->name) ?></td>
                            <td><?= $service->price === null ?
                                '' : $this->Number->currency($service->price->toString()) ?></td>
                            <td>
                                <?= $service->service_type !== null ? $this->Html->link(
                                    $service->service_type->name ?? '(' . $service->service_type->id . ')',
                                    ['controller' => 'ServiceTypes', 'action' => 'view', $service->service_type->id],
                                ) : '' ?>
                            </td>
                            <td><?= $service->currently_offered ? __('Yes') : __('No'); ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Services', 'action' => 'view', $service->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Services', 'action' => 'edit', $service->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Services', 'action' => 'delete', $service->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $service->id)],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
