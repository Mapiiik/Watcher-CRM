<?php
/**
 * What the contract checks have to say about this one contract.
 *
 * Each finding is drawn by the check's own listing, the same one the overview uses, so that
 * a check added later shows up here without being told to. The contract column is dropped:
 * on a contract's own page every row is about that contract.
 *
 * @var \App\View\AppView $this
 * @var list<array{check: \App\Contracts\Check\ContractCheckInterface, records: iterable<\Cake\Datasource\EntityInterface>}> $problems
 */
?>
<?php if ($problems !== []) : ?>
    <div class="message warning" role="alert">
        <strong><?= __('This contract does not add up') ?></strong>
    </div>

    <?php foreach ($problems as $problem) : ?>
        <div class="related" id="problem-<?= h($problem['check']->id()) ?>">
            <h4><?= h($problem['check']->title()) ?></h4>
            <?= $this->element('ContractChecks/' . $problem['check']->template(), [
                'records' => $problem['records'],
                'contract_column' => false,
            ]) ?>
        </div>
    <?php endforeach ?>
<?php endif ?>
