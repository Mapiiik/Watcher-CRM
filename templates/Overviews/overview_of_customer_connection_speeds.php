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
            <h3><?= __('Overview of Customer Connection Speeds')
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
                            <th><?= __('City') ?></th>
                            <th><?= __('Active Connections') ?></th>
                            <th><?= __('Advertised  0-2 Mbps') ?></th>
                            <th><?= __('Advertised  2-10 Mbps') ?></th>
                            <th><?= __('Advertised  10-30 Mbps') ?></th>
                            <th><?= __('Advertised  30-100 Mbps') ?></th>
                            <th><?= __('Advertised  100-1000 Mbps') ?></th>
                            <th><?= __('Advertised  1000+ Mbps') ?></th>
                            <th><?= __('Active Connections') . ' ' . __('(nonbusiness)') ?></th>
                            <th><?= __('Advertised  0-2 Mbps') . ' ' . __('(nonbusiness)') ?></th>
                            <th><?= __('Advertised  2-10 Mbps') . ' ' . __('(nonbusiness)') ?></th>
                            <th><?= __('Advertised  10-30 Mbps') . ' ' . __('(nonbusiness)') ?></th>
                            <th><?= __('Advertised  30-100 Mbps') . ' ' . __('(nonbusiness)') ?></th>
                            <th><?= __('Advertised  100-1000 Mbps') . ' ' . __('(nonbusiness)') ?></th>
                            <th><?= __('Advertised  1000+ Mbps') . ' ' . __('(nonbusiness)') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connection_points as $connection_point) : ?>
                        <tr>
                            <td><?= h($connection_point->city) ?></td>
                            <td><?= $connection_point->active_connections === null ?
                                '' : $this->Number->format($connection_point->active_connections)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds->speed_0_2 === null ?
                                '' : $this->Number->format($connection_point->advertised_speeds->speed_0_2)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds->speed_2_10 === null ?
                                '' : $this->Number->format($connection_point->advertised_speeds->speed_2_10)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds->speed_10_30 === null ?
                                '' : $this->Number->format($connection_point->advertised_speeds->speed_10_30)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds->speed_30_100 === null ?
                                '' : $this->Number->format($connection_point->advertised_speeds->speed_30_100)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds->speed_100_1000 === null ?
                                '' : $this->Number->format($connection_point->advertised_speeds->speed_100_1000)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds->speed_1000_plus === null ?
                                '' : $this->Number->format($connection_point->advertised_speeds->speed_1000_plus)
                            ?></td>
                            <td><?= $connection_point->active_connections_nonbusiness === null ?
                                '' : $this->Number->format($connection_point->active_connections_nonbusiness) ?>
                            </td>
                            <td><?= $connection_point->advertised_speeds_nonbusiness->speed_0_2 === null ?
                                '' :
                                $this->Number->format($connection_point->advertised_speeds_nonbusiness->speed_0_2)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds_nonbusiness->speed_2_10 === null ?
                                '' :
                                $this->Number->format($connection_point->advertised_speeds_nonbusiness->speed_2_10)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds_nonbusiness->speed_10_30 === null ?
                                '' :
                                $this->Number->format($connection_point->advertised_speeds_nonbusiness->speed_10_30)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds_nonbusiness->speed_30_100 === null ?
                                '' :
                                $this->Number->format($connection_point->advertised_speeds_nonbusiness->speed_30_100)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds_nonbusiness->speed_100_1000 === null ?
                                '' :
                                $this->Number->format($connection_point->advertised_speeds_nonbusiness->speed_100_1000)
                            ?></td>
                            <td><?= $connection_point->advertised_speeds_nonbusiness->speed_1000_plus === null ?
                                '' :
                                $this->Number->format($connection_point->advertised_speeds_nonbusiness->speed_1000_plus)
                            ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
