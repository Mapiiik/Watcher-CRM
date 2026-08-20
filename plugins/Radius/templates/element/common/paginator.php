<?php
/**
 * The pager under a listing of the plugin's.
 *
 * A copy of the application's rather than a call to it: the plugin carries its own catalogue and
 * says nothing through anyone else's, so the five words below have to be extracted here.
 *
 * @var \App\View\AppView $this
 */
?>
<div class="paginator">
    <ul class="pagination">
        <?= $this->Paginator->first('<< ' . __d('radius', 'first')) ?>
        <?= $this->Paginator->prev('< ' . __d('radius', 'previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__d('radius', 'next') . ' >') ?>
        <?= $this->Paginator->last(__d('radius', 'last') . ' >>') ?>
    </ul>
    <p><?= $this->Paginator->counter(
        __d('radius', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
    ) ?></p>
</div>
