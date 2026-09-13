<?php
/**
 * @var \App\View\AppView $this
 * @var list<\App\Customers\Check\CustomerCheckInterface> $checks
 * @var array<string, bool> $shown
 * @var bool $ignore_inactive
 * @var array<string, \Cake\Datasource\ResultSetInterface<int, \Cake\Datasource\EntityInterface>> $results
 */
?>
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
    <div class="choices">
        <?php foreach ($checks as $check) : ?>
            <?= $this->Form->control('checks.' . $check->id(), [
                'type' => 'checkbox',
                'label' => $check->title(),
                'checked' => $shown[$check->id()],
                'value' => 1,
                'onchange' => $this::SUBMIT_ON_CHANGE,
            ]) ?>
        <?php endforeach ?>
    </div>
</fieldset>
<?= $this->Form->end() ?>

<div class="overviews index content">
    <?= $this->AuthLink->link(__('List Overviews'), ['action' => 'index'], ['class' => 'button float-right']) ?>
    <?= $this->heading(__('Customer Problems')) ?>

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
