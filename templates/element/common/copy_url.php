<?php
/**
 * Button copying a URL to the clipboard.
 *
 * Saves selecting the address out of the browser bar when a link is pasted
 * somewhere else, for instance into a calendar entry when planning work.
 *
 * @var \App\View\AppView $this
 * @var string $url URL to copy, absolute if it is going anywhere else
 * @var string|null $label Button label
 * @var bool|null $clear Contain the float, see below
 */

// The helper skips a script it has already included, so callers do not have to
// care whether another element on the page pulled it in first.
$this->Html->script('copy-url.js', ['block' => true]);

$label ??= __('Copy Link');
$clear ??= false;

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
