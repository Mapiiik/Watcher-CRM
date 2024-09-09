<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="message warning" onclick="this.classList.add('hidden');"><?= $message ?></div>