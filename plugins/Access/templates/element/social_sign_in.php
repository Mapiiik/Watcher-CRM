<?php
/**
 * Signing in with an identity at another provider, where any is offered.
 *
 * @var \App\View\AppView $this
 */

use Access\Social\Providers;

// the page somebody was on their way to, for when they come back from the provider
$redirect = $this->getRequest()->getQuery('redirect');
$query = is_string($redirect) && $redirect !== '' ? ['redirect' => $redirect] : [];
?>
<?php foreach (Providers::names() as $provider) : ?>
    <?= $this->Html->link(
        __d('access', 'Sign in with {0}', Providers::label($provider)),
        ['action' => 'socialLogin', 'provider' => $provider, '?' => $query],
        ['class' => 'button button-outline'],
    ) ?>
<?php endforeach;
