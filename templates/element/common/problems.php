<?php
/**
 * What the checks have to say about the record being looked at.
 *
 * Each finding is drawn by the check's own listing, the same one the overview uses, and the
 * check says which listing that is - so a check added later shows up here without being told
 * to, whichever family it belongs to.
 *
 * A column saying what a row is about is worth dropping where the page already says it: the
 * contract on one contract's page, the customer on a customer's. The listings take that as a
 * hint and only honour it where they carry such a column at all.
 *
 * @var \App\View\AppView $this
 * @var list<array{check: \App\Check\CheckInterface, records: iterable<\Cake\Datasource\EntityInterface>}> $problems
 * @var bool|null $contract_column Whether a row should say which contract it is about.
 * @var bool|null $customer_column Whether a row should say which customer it is about.
 */

$contract_column ??= true;
$customer_column ??= true;
?>
<?php if ($problems !== []) : ?>
    <div class="message warning" role="alert">
        <?php // Not every check is a fault - a lapsed contract signed again looks exactly like ?>
        <?php // a mistyped date - so this says what was found rather than what it means. ?>
        <strong><?= __('These do not add up, so something here is probably not right') ?></strong>
    </div>

    <?php foreach ($problems as $problem) : ?>
        <div class="related" id="problem-<?= h($problem['check']->id()) ?>">
            <h4><?= h($problem['check']->title()) ?></h4>
            <?= $this->element($problem['check']->element(), [
                'records' => $problem['records'],
                'contract_column' => $contract_column,
                'customer_column' => $customer_column,
            ]) ?>
        </div>
    <?php endforeach ?>
    <hr>
<?php endif ?>
