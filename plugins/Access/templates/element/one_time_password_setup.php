<?php
/**
 * How to set an authenticator application up: the QR code to scan, and the key to type where it
 * cannot be.
 *
 * @var \App\View\AppView $this
 * @var string $secretDataUri
 * @var string $secret
 */
?>
<p><?= __d(
    'access',
    'Scan the QR code with an authenticator application on your phone, such as Google Authenticator, '
    . 'Microsoft Authenticator or FreeOTP, and enter the six-digit verification code it shows.',
) ?></p>
<p><?= $this->Html->image($secretDataUri, [
    'alt' => __d('access', 'QR code for the authenticator application'),
    'width' => 200,
    'height' => 200,
]) ?></p>
<p>
    <?= __d('access', 'Where the QR code cannot be scanned, enter this key instead:') ?>
    <code><?= h(trim(chunk_split($secret, 4, ' '))) ?></code>
</p>
