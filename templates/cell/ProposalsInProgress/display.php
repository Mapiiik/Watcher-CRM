<?php
/**
 * The proposals still being worked on, or nothing at all.
 *
 * @var \App\View\AppView $this
 * @var array<array<string, mixed>> $proposals
 */

if ($proposals === []) {
    return;
}
?>
<div class="related">
    <h4 id="proposals"><?= __('Proposals in Progress') ?></h4>
    <?= $this->element('Documents/proposals', [
        'proposals' => $proposals,
        'working' => true,
        'showCustomer' => false,
    ]) ?>
</div>
