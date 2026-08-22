<?php
/**
 * A remark that Watcher NMS did not answer, where a whole message would be too much.
 *
 * Drawn from the answer the caller has in hand, so it appears beside the very reading that came to
 * nothing. An answer that arrived - even an empty one - and a question nobody asked, because this
 * installation has no network management system, both draw nothing. That is what keeps the mark
 * from becoming furniture and lets it mean something the one time it appears.
 *
 * @var \App\View\AppView $this
 * @var \App\Http\Answer<mixed> $answer What came of the reading this cell was to be filled from.
 */

if (!$answer->unanswered()) {
    return;
}

echo $this->Html->tag('span', '⚠', [
    'class' => 'warning-text',
    'title' => __('Data from Watcher NMS could not be loaded.'),
]);
