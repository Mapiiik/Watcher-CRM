<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $cto_categories
 * @var \Cake\I18n\Date $month_to_display
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Overviews'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="overviews index content">
            <?php foreach ($cto_categories as $cto_category => $connection_points) : ?>
                <?= $this->AuthLink->link(
                    __('Export') . ' ' . $cto_category,
                    [
                        '_ext' => 'csv',
                        $cto_category,
                        '?' => ['month_to_display' => $month_to_display->i18nFormat('yyyy-MM')],
                    ],
                    ['class' => 'button float-right']
                ) ?>
            <?php endforeach; ?>
            <h3><?= __('Overview of Customer Connection Points')
                . ' - '
                . $month_to_display->i18nFormat('LLLL yyyy') ?></h3>

            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
            <div class="row">
                <div class="column">
                    <?= $this->Form->control('month_to_display', [
                        'label' => __('Month To Display'),
                        'placeholder' => __('YYYY-MM'),
                        'type' => 'month',
                        'onchange' => 'this.form.submit();',
                    ]) ?>
                </div>
            </div>
            <?= $this->Form->end() ?>

            <?php foreach ($cto_categories as $cto_category => $connection_points) : ?>
            <div class="table-responsive">
            <h4><?= $cto_category ?></h4>
                <table>
                    <thead>
                        <tr>
                            <th><?= __('RUIAN GID') ?></th>
                            <th><?= __('Active Connections') ?></th>
                            <th><?= __('Active Connections (nonbusiness)') ?></th>
                            <th><?= __('Active 0-30 Mbps') ?></th>
                            <th><?= __('Active 30-100 Mbps') ?></th>
                            <th><?= __('Active 100+ Mbps') ?></th>
                            <th><?= __('Available Connections') ?></th>
                            <th><?= __('Available Effective Download Speed Category') ?></th>
                            <th><?= __('Available Effective Upload Speed Category') ?></th>
                            <th><?= __('Available Maximal Download Speed Category') ?></th>
                            <th><?= __('Available Maximal Upload Speed Category') ?></th>
                            <th><?= __('VHCN Network Category') ?></th>
                            <th><?= __('Address') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connection_points as $connection_point) : ?>
                        <tr>
                            <td><?= $this->Number->format($connection_point->ruian_gid) ?></td>
                            <td><?= $connection_point->active_connections === null ?
                                '' : $this->Number->format($connection_point->active_connections) ?></td>
                            <td><?= $connection_point->active_connections_nonbusiness === null ?
                                '' : $this->Number->format($connection_point->active_connections_nonbusiness) ?></td>
                            <td><?= $connection_point->active_speeds->speed_0_30 === null ?
                                '' : $this->Number->format($connection_point->active_speeds->speed_0_30) ?></td>
                            <td><?= $connection_point->active_speeds->speed_30_100 === null ?
                                '' : $this->Number->format($connection_point->active_speeds->speed_30_100) ?></td>
                            <td><?= $connection_point->active_speeds->speed_100_plus === null ?
                                '' : $this->Number->format($connection_point->active_speeds->speed_100_plus) ?></td>
                            <td><?= $connection_point->available_connections === null ?
                                '' : $this->Number->format($connection_point->available_connections) ?></td>
                            <td><?= h($connection_point->available_speeds->effective_download_category) ?></td>
                            <td><?= h($connection_point->available_speeds->effective_upload_category) ?></td>
                            <td><?= h($connection_point->available_speeds->maximal_download_category) ?></td>
                            <td><?= h($connection_point->available_speeds->maximal_upload_category) ?></td>
                            <td><?= $this->Number->format($connection_point->vhcn_category) ?></td>
                            <td><?= h($connection_point->ruian_address) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(__('View'), [
                                    'plugin' => 'Ruian',
                                    'controller' => 'Addresses',
                                    'action' => 'view',
                                    $connection_point->ruian_gid,
                                ]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
