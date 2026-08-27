<?php
/**
 * What the checks have to say about the record being looked at.
 *
 * Each finding is drawn by the check's own listing, the same one the overview uses, and the
 * check says which listing that is - so a check added later shows up here without being told
 * to, whichever family it belongs to.
 *
 * A column saying what a row is about is dropped where the page already says it: the contract
 * on one contract's page, the customer on a customer's. The listings take that as a hint and
 * only honour it where they carry such a column at all.
 *
 * @var \App\View\AppView $this
 * @var list<array{check: \App\Check\CheckInterface, records: iterable<\Cake\Datasource\EntityInterface>}> $problems
 * @var bool|null $one_contract Whether this is a single contract's own page.
 * @var bool|null $one_customer Whether this is a single customer's own page.
 */

$one_contract ??= false;
$one_customer ??= false;
?>
<?php if ($problems !== []) : ?>
    <div class="message warning" role="alert">
        <strong>
            <?php if ($one_contract) : ?>
                <?= __('This contract does not add up') ?>
            <?php else : ?>
                <?= __('These records do not add up') ?>
            <?php endif ?>
        </strong>
    </div>

    <?php foreach ($problems as $problem) : ?>
        <div class="related" id="problem-<?= h($problem['check']->id()) ?>">
            <h4><?= h($problem['check']->title()) ?></h4>
            <?= $this->element($problem['check']->element(), [
                'records' => $problem['records'],
                'contract_column' => !$one_contract,
                'customer_column' => !$one_customer,
            ]) ?>
        </div>
    <?php endforeach ?>
    <hr>
<?php endif ?>
