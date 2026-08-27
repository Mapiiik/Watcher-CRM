<?php
/**
 * @var \App\View\AppView $this
 * @var list<\App\Contracts\Check\ContractCheckInterface> $checks
 * @var array<string, bool> $shown
 * @var bool $ignore_inactive
 * @var array<string, \Cake\Datasource\ResultSetInterface<int, \Cake\Datasource\EntityInterface>> $results
 */
?>
<div class="overviews index content">
    <h3><?= __('Contract Problems') ?></h3>

    <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => 'query']) ?>
    <fieldset>
        <?= $this->Form->control('ignore_inactive', [
            'type' => 'checkbox',
            'label' => __('Ignore what is no longer running'),
            'checked' => $ignore_inactive,
            'value' => 1,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
        <hr />
        <?php foreach ($checks as $check) : ?>
            <?= $this->Form->control('checks.' . $check->id(), [
                'type' => 'checkbox',
                'label' => $check->title(),
                'checked' => $shown[$check->id()],
                'value' => 1,
                'onchange' => $this::SUBMIT_ON_CHANGE,
            ]) ?>
        <?php endforeach ?>
    </fieldset>
    <?= $this->Form->end() ?>

    <div class="table-responsive">
        <?php foreach ($checks as $check) : ?>
            <?php if (!$shown[$check->id()]) : ?>
                <?php continue ?>
            <?php endif ?>
            <?php $records = $results[$check->id()] ?>
            <div class="related" id="<?= h($check->id()) ?>">
                <h4><?= h($check->title()) ?> (<?= h((string)count($records)) ?>)</h4>
                <?php if (count($records) === 0) : ?>
                    <p><?= h($check->emptyMessage()) ?></p>
                <?php else : ?>
                    <?= $this->element($check->element(), ['records' => $records]) ?>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>
</div>
