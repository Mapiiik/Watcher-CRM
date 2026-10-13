<?php
/**
 * How to set an authenticator app up: the QR code to scan, and the key to type where it cannot be.
 *
 * @var \App\View\AppView $this
 * @var string $secretDataUri
 * @var string $secret
 */
?>
<p><?= __d(
    'access',
    'Scan the code with an authenticator app on your phone, such as Google Authenticator, '
    . 'Microsoft Authenticator or FreeOTP, and enter the six digits it shows.',
) ?></p>
<p><?= $this->Html->image($secretDataUri, [
    'alt' => __d('access', 'QR code for the authenticator app'),
    'width' => 200,
    'height' => 200,
]) ?></p>
<p>
    <?= __d('access', 'Where the code cannot be scanned, enter this key instead:') ?>
    <code><?= h(trim(chunk_split($secret, 4, ' '))) ?></code>
</p>
