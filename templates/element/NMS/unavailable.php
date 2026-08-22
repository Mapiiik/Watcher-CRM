<?php
/**
 * A remark that Watcher NMS did not answer, where a whole message would be too much.
 *
 * It is only ever drawn where a question actually went unanswered while this page was being put
 * together. An installation with no network management system at all, and a page that asked it
 * nothing, get nothing - which is what keeps the mark from becoming furniture and lets it mean
 * something the one time it appears.
 *
 * @var \App\View\AppView $this
 */

use App\NMS\ApiClient;

if (ApiClient::isAvailable() !== false) {
    return;
}

echo $this->Html->tag('span', '⚠', [
    'class' => 'warning-text',
    'title' => __('Data from Watcher NMS could not be loaded.'),
]);
