<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Form\Form $filterForm
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $taskTypes
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $taskStates
 * @var array<string, \Maps\Marker> $mapMarkers
 * @var array<string, \Maps\Polyline> $mapPolylines
 */
?>
<?= $this->Form->create($filterForm, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('task_type_ids', [
            'label' => __('Task Type'),
            'options' => $taskTypes,
            'multiple' => 'multiple',
            'style' => 'height: 100px;',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('task_state_ids', [
            'label' => __('Task State'),
            'options' => $taskStates,
            'multiple' => 'multiple',
            'style' => 'height: 100px;',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="tasks map content">
    <?= $this->AuthLink->link(__('List Tasks'), ['action' => 'index'], ['class' => 'button float-right']) ?>
    <h3><?= __('Tasks') ?></h3>
    <?php if ($mapMarkers === []) : ?>
        <p><?= __('No open task has a place on the map.') ?></p>
    <?php else : ?>
        <?= $this->element('Maps.Maps/overview', [
            'mapMarkers' => $mapMarkers,
            'mapPolylines' => $mapPolylines,
            'mapHeight' => '600px',
        ]) ?>
    <?php endif; ?>
</div>
