<?php
/**
 * A control copying a URL to the clipboard.
 *
 * Saves selecting the address out of the browser bar when a link is pasted
 * somewhere else, for instance into a calendar entry when planning work.
 *
 * It comes in two shapes, because it is wanted in two places. Standing on its
 * own beside a heading it is a button, and it floats so the heading flows
 * around it. Standing among other links - the actions at the end of a row in a
 * listing - it is a link like the ones beside it, and `.actions a` then dresses
 * it without a rule of its own.
 *
 * @var \App\View\AppView $this
 * @var string $url URL to copy, absolute if it is going anywhere else
 * @var string|null $label What the control says
 * @var bool|null $as_link Draw it as a link rather than as a button
 * @var bool|null $clear Contain the float, see below
 */

// The helper skips a script it has already included, so callers do not have to
// care whether another element on the page pulled it in first. A listing asks
// for this once a row and still gets the one script tag.
$this->Html->script('copy-url.js', ['block' => true]);

$label ??= __('Copy Link');
$as_link ??= false;
$clear ??= false;

if ($as_link) {
    /*
     * The address it copies is also where it points. The script takes the click
     * and calls `preventDefault()`, so following it never happens - but where
     * the script has not run, a click opens the record instead of jumping to
     * the top of the page, which is what a dead `href="#"` would do.
     */
    echo $this->Html->link($label, $url, [
        'data-copy-url' => $url,
        'data-copied' => __('Copied'),
    ]);

    return;
}

$button = $this->Html->tag('button', h($label), [
    'type' => 'button',
    'class' => 'button button-small float-right',
    'data-copy-url' => $url,
    'data-copied' => __('Copied'),
]);

/*
 * The button floats, so a heading next to it simply flows around it. A flex
 * container does not: it shrinks away from the float instead, which narrows a
 * form placed below the button. Those callers pass `clear` to get a wrapper
 * containing the float.
 */
echo $clear ? $this->Html->div('clearfix', $button) : $button;
