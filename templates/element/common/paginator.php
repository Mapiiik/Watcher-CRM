<?php
/**
 * The pager under a listing.
 *
 * Every listing in the application pages the same way and says so in the same words, so the words
 * are written here once. They belong to this element's own catalogue - the application's - whoever
 * draws it, which is why nothing here asks the caller what to translate against.
 *
 * @var \App\View\AppView $this
 */
?>
<div class="paginator">
    <ul class="pagination">
        <?= $this->Paginator->first('<< ' . __('first')) ?>
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
        <?= $this->Paginator->last(__('last') . ' >>') ?>
    </ul>
    <p><?= $this->Paginator->counter(
        __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
    ) ?></p>
</div>
