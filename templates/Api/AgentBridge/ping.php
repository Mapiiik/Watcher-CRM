<?php
/**
 * @var \App\View\AppView $this
 * @var array|false $pingResults
 * @var string $pingImage
 */

if (!empty($pingImage)) {
    echo $this->Html->image('ping/' . $pingImage, [
        'class' => 'ping-status',
    ]);
}
