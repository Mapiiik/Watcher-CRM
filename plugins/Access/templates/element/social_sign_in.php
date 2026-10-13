<?php
/**
 * Signing in with an identity at another provider, where any is offered.
 *
 * @var \App\View\AppView $this
 */

use Access\Social\Providers;
?>
<?php foreach (Providers::names() as $provider) : ?>
    <?= $this->Html->link(
        __d('access', 'Sign in with {0}', Providers::label($provider)),
        ['action' => 'socialLogin', 'provider' => $provider],
        ['class' => 'button button-outline'],
    ) ?>
<?php endforeach;
