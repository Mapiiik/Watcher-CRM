<?php
/**
 * @var \App\View\AppView $this
 * @var array{id: string, username: string, token: string} $made
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('access', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('access', 'View User'),
                ['action' => 'view', $made['id']],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <?= $this->record(__d('access', 'API Key'), $made['username']) ?>
            <p>
                <?= __d(
                    'access',
                    'The client signs in with this key in the Authorization header, as a Bearer token. '
                    . 'It is shown only now. The key the account had before no longer works.',
                ) ?>
            </p>
            <p><code><?= h($made['token']) ?></code></p>
        </div>
    </div>
</div>
