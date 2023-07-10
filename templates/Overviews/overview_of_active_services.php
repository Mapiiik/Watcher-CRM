<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $services
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
            <h3><?= __('Overview of active services')
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
                <div class="column">
                    <?= $this->Form->control('service_type_id', [
                        'empty' => true,
                        'onchange' => 'this.form.submit();',
                    ]) ?>
                </div>
                <div class="column">
                    <?= $this->Form->control('cto_category', [
                        'empty' => true,
                        'onchange' => 'this.form.submit();',
                    ]) ?>
                </div>
                <div class="column">
                    <?= $this->Form->control('access_point_id', [
                        'empty' => true,
                        'onchange' => 'this.form.submit();',
                    ]) ?>
                </div>
            </div>
            <?= $this->Form->end() ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= $this->Paginator->sort('name') ?></th>
                            <th><?= $this->Paginator->sort('price') ?></th>
                            <th><?= $this->Paginator->sort('ServiceTypes.name', __('Service Type')) ?></th>
                            <th><?= $this->Paginator->sort('Queues.name', __('Queue')) ?></th>
                            <th><?= __('Number of Uses') ?></th>
                            <th><?= __('Number of Uses (nonbusiness)') ?></th>
                            <th><?= __('Sum') ?></th>
                            <th><?= __('Fixed Discount Sum') ?></th>
                            <th><?= __('Percentage Discount Sum') ?></th>
                            <th><?= __('Total Sum') ?></th>
                            <th><?= __('Total Sum (nonbusiness)') ?></th>
                            <th><?= __('Total Sum (unbilled)') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service) : ?>
                        <tr>
                            <td><?= h($service->name) ?></td>
                            <td><?= $service->price === null ?
                                '' : $this->Number->currency($service->price) ?></td>
                            <td><?= $service->__isset('service_type') ?
                                $this->Html->link(
                                    $service->service_type->name,
                                    [
                                        'controller' => 'ServiceTypes',
                                        'action' => 'view',
                                        $service->service_type->id,
                                    ]
                                ) : '' ?></td>
                            <td><?= $service->__isset('queue') ? $this->Html->link($service->queue->name, [
                                'controller' => 'Queues',
                                'action' => 'view',
                                $service->queue->id,
                            ]) : '' ?></td>
                            <td><?= $service->number_of_uses === null ?
                                '' : $this->Number->format($service->number_of_uses) ?></td>
                            <td><?= $service->number_of_uses_nonbusiness === null ?
                                '' : $this->Number->format($service->number_of_uses_nonbusiness) ?></td>
                            <td><?= $service->sum === null ?
                                '' : $this->Number->currency($service->sum) ?></td>
                            <td><?= $service->fixed_discount_sum === null ?
                                '' : $this->Number->currency($service->fixed_discount_sum) ?></td>
                            <td><?= $service->percentage_discount_sum === null ?
                                '' : $this->Number->currency($service->percentage_discount_sum) ?></td>
                            <td><?= $service->total_sum === null ?
                                '' : $this->Number->currency($service->total_sum) ?></td>
                            <td><?= $service->total_sum_nonbusiness === null ?
                                '' : $this->Number->currency($service->total_sum_nonbusiness) ?></td>
                            <td><?= $service->total_sum_unbilled === null ?
                                '' : $this->Number->currency($service->total_sum_unbilled) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(__('View'), [
                                    'controller' => 'Services',
                                    'action' => 'view',
                                    $service->id,
                                ]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div>
                <?= __('Total Sum') . ': ' . $this->Number->currency($services->sumOf('total_sum')) ?><br>
            </div>
        </div>
    </div>
</div>
