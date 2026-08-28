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
 * A customer holding a dozen contracts can gather enough findings to push the record itself
 * off the screen, so a long banner is cut to a fixed height and opened by the reader. Nothing
 * is left out - what is cut away is a scroll, not a query.
 *
 * @var \App\View\AppView $this
 * @var list<array{check: \App\Check\CheckInterface, records: \Countable&iterable<\Cake\Datasource\EntityInterface>}> $problems
 * @var bool|null $contract_column Whether a row should say which contract it is about.
 * @var bool|null $customer_column Whether a row should say which customer it is about.
 */

$contract_column ??= true;
$customer_column ??= true;

// CSS cannot tell whether the content outgrew the height it is cut to, and an opener that
// uncovers nothing is worse than none at all, so the height is guessed here and compared:
// a check costs about eight rem before its rows - heading, sentence, the head of its table -
// and each of its rows about four on top.
$rows = 0;
foreach ($problems as $problem) {
    $rows += count($problem['records']);
}
$cut = 26;
$clamped = count($problems) * 8 + $rows * 4 > $cut;
$toggle = 'problems-toggle-' . uniqid();
?>
<?php if ($problems !== []) : ?>
    <div class="message warning" role="alert">
        <?php // Not every check is a fault - a lapsed contract signed again looks exactly like ?>
        <?php // a mistyped date - so this says what was found rather than what it means. ?>
        <strong><?= __('These do not add up, so something here is probably not right') ?></strong>
    </div>

    <?php if ($clamped) : ?>
        <input type="checkbox" id="<?= h($toggle) ?>" class="problems-toggle">
    <?php endif ?>
    <div
        class="problems-viewport<?= $clamped ? ' clamped' : '' ?>"
        style="--problems-cut: <?= $cut ?>rem"
    >
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
    </div>
    <?php if ($clamped) : ?>
        <label for="<?= h($toggle) ?>" class="problems-more">
            <span class="when-folded">⇣ <?= __('more') ?> ⇣</span>
            <span class="when-unfolded">⇡ <?= __('less') ?> ⇡</span>
        </label>
    <?php endif ?>
    <hr>
<?php endif ?>
