<?php
/**
 * How to set an authenticator application up: the hash function, the QR code to scan, and the key
 * to type where it cannot be. Choosing another hash function sends the form of the code, which is
 * where the element is rendered, for a new QR code.
 *
 * @var \App\View\AppView $this
 * @var string $secretDataUri
 * @var string $secret
 * @var \Access\Authentication\OneTimePasswordAlgorithm $algorithm
 */

use Access\Authentication\OneTimePasswordAlgorithm;

?>
<p><?= $algorithm === OneTimePasswordAlgorithm::Sha1
    ? __d(
        'access',
        'Scan the QR code with an authenticator application on your phone, such as Google Authenticator, '
        . 'Microsoft Authenticator or Bitwarden Authenticator, and enter the six-digit verification code it shows.',
    )
    : __d(
        'access',
        'Scan the QR code with an authenticator application on your phone that understands {0}, such as '
        . 'Google Authenticator or Bitwarden Authenticator, and enter the six-digit verification code it shows. '
        . 'Microsoft Authenticator does not understand it and shows codes that are not right.',
        $algorithm->title(),
    ) ?></p>
<br>
<?= $this->Form->control('algorithm', [
    'type' => 'select',
    'label' => __d('access', 'Algorithm'),
    'options' => OneTimePasswordAlgorithm::options(),
    'value' => $algorithm->value,
    'onchange' => $this::REFRESH_ON_CHANGE,
]) ?>
<?php $this->Form->unlockField('refresh'); ?>
<p><?= $this->Html->image($secretDataUri, [
    'alt' => __d('access', 'QR code for the authenticator application'),
    'width' => 200,
    'height' => 200,
]) ?></p>
<p>
    <?= __d('access', 'Where the QR code cannot be scanned, enter this key instead:') ?>
    <code><?= h(trim(chunk_split($secret, 4, ' '))) ?></code>
</p>
