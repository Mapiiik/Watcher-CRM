<?php
/**
 * What the contract checks have to say about what is being looked at.
 *
 * Each finding is drawn by the check's own listing, the same one the overview uses, so that a
 * check added later shows up here without being told to.
 *
 * Drawn on one contract's page the contract column is dropped - every row there is about that
 * contract already - and the heading says so. On a customer's page the findings come from
 * every contract they hold, so the column stays and names which.
 *
 * @var \App\View\AppView $this
 * @var list<array{check: \App\Contracts\Check\ContractCheckInterface, records: iterable<\Cake\Datasource\EntityInterface>}> $problems
 * @var bool|null $one_contract Whether this is a single contract's own page.
 */

$one_contract ??= false;
?>
<?php if ($problems !== []) : ?>
    <div class="message warning" role="alert">
        <strong>
            <?php if ($one_contract) : ?>
                <?= __('This contract does not add up') ?>
            <?php else : ?>
                <?= __('These contracts do not add up') ?>
            <?php endif ?>
        </strong>
    </div>

    <?php foreach ($problems as $problem) : ?>
        <div class="related" id="problem-<?= h($problem['check']->id()) ?>">
            <h4><?= h($problem['check']->title()) ?></h4>
            <?= $this->element('ContractChecks/' . $problem['check']->template(), [
                'records' => $problem['records'],
                'contract_column' => !$one_contract,
            ]) ?>
        </div>
    <?php endforeach ?>
    <hr>
<?php endif ?>
