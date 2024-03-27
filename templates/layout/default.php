<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use App\Versioning;
use Cake\Core\Configure;

/**
 * @var \App\View\AppView $this
 * @psalm-scope-this \App\View\AppView
 */

$cakeDescription = 'Watcher CRM | ' . env('APP_COMPANY', 'ISP');

$request = $this->getRequest();

$controller = $this->getName();
$action = $request->getParam('action');

$buttonSelected = function ($haystack = []) use ($controller, $action) {
    if (in_array($controller, $haystack)) {
        return ' button-selected';
    } elseif (in_array($action, $haystack)) {
        return ' button-selected';
    } else {
        return '';
    }
};

$urlWithQuery = function ($query = []) use ($request) {
    return $this->Url->build(
        ['?' => $query + $request->getQueryParams()] + $request->getParam('pass')
    );
};
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $cakeDescription ?> |
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->script([
        'https://code.jquery.com/jquery.min.js',
        'links.js',
        'htmx.js',
    ]) ?>

    <?php if (filter_var(env('ENABLE_SELECT2', false), FILTER_VALIDATE_BOOLEAN)) : ?>
        <?= $this->Html->css(['https://cdn.jsdelivr.net/npm/select2@4.0/dist/css/select2.min.css']) ?>
        <?= $this->Html->script([
            'https://cdn.jsdelivr.net/npm/select2@4.0/dist/js/select2.min.js',
            'select2-settings.js',
        ]) ?>
    <?php endif ?>

    <?php
    switch (Configure::read('UI.theme')) {
        case 'legacy':
            echo $this->Html->css(['normalize.min', 'legacy']);
            break;
        case 'dark':
            echo $this->Html->css(['normalize.min', 'milligram.min', 'cake', 'dark']);
            break;
        case 'contrast':
            echo $this->Html->css(['normalize.min', 'milligram.min', 'cake', 'high_contrast']);
            break;
        default:
            echo $this->Html->css(['normalize.min', 'milligram.min', 'cake']);
    }
    ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>

    <?php
    // automatic closing of the window after 3 seconds
    if (
        $request->getQuery('win-link') == 'true'
        && $request->getQuery('auto-close') == 'true'
    ) : ?>
    <script>
        window.onload = function(){
            setTimeout(
                function(){
                    window.close();
                },
                3000
            );
        };
    </script>
    <?php endif; ?>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-title">
            <a href="<?= $this->Url->build('/') ?>"><span>Watcher</span> CRM</a>
        </div>

        <?php if (!($request->getQuery('win-link') == 'true')) : ?>
        <div class="top-nav-links">
            <?= $this->AuthLink->link(
                __('Customers'),
                ['controller' => 'Customers', 'action' => 'index', 'plugin' => null],
                ['class' => 'button button-small' . $buttonSelected(['Customers'])]
            ) ?>
            <?= $this->AuthLink->link(
                __('Tasks'),
                ['controller' => 'Tasks', 'action' => 'index', 'plugin' => null, 'customer_id' => false],
                ['class' => 'button button-small' . $buttonSelected(['Tasks'])]
            ) ?>
            <?= $this->AuthLink->link(
                __('Bookkeeping'),
                ['controller' => 'Invoices', 'action' => 'index', 'plugin' => 'BookkeepingPohoda'],
                ['class' => 'button button-small' . $buttonSelected(['Invoices'])]
            ) ?>
            <?= $this->AuthLink->link(
                __('RADIUS'),
                ['controller' => 'Accounts', 'action' => 'index', 'plugin' => 'Radius', 'customer_id' => false],
                ['class' => 'button button-small' . $buttonSelected([
                    'Accounts',
                    'Nass',
                    'Radacct',
                    'Radcheck',
                    'Radgroupcheck',
                    'Radgroupreply',
                    'Radpostauth',
                    'Radreply',
                    'Radusergroup',
                ])]
            ) ?>
            <?= $this->AuthLink->link(
                __('RÚIAN'),
                ['controller' => 'Addresses', 'action' => 'index', 'plugin' => 'Ruian'],
                ['class' => 'button button-small' . $buttonSelected(['Addresses'])]
            ) ?>
            <?= $this->AuthLink->link(
                __('Overviews'),
                ['controller' => 'Overviews', 'action' => 'index', 'plugin' => null, 'customer_id' => false],
                ['class' => 'button button-small' . $buttonSelected(['Overviews'])]
            ) ?>
            <?= $this->AuthLink->link(
                __('Settings'),
                ['controller' => 'Settings', 'action' => 'index', 'plugin' => null, 'customer_id' => false],
                ['class' => 'button button-small' . $buttonSelected([
                    'Settings',
                    'AppUsers',
                ])]
            ) ?>

            <?php if (file_exists(WWW_ROOT . 'legacy' . DS) && is_dir(WWW_ROOT . 'legacy' . DS)) : ?>
                <?php if ($request->getParam('customer_id')) { ?>
                    <?= $this->Html->link(
                        __('Old NMS'),
                        '/legacy/redirect.php?customer_id=' . $request->getParam('customer_id'),
                        ['class' => 'button button-small']
                    ) ?>
                <?php } elseif ($this->getName() == 'Tasks' && $request->getParam('id')) { ?>
                    <?= $this->Html->link(
                        __('Old NMS'),
                        '/legacy/redirect.php?task_id=' . $request->getParam('id'),
                        ['class' => 'button button-small']
                    ) ?>
                <?php } else { ?>
                    <?= $this->Html->link(__('Old NMS'), '/legacy', ['class' => 'button button-small']) ?>
                <?php } ?>
            <?php endif; ?>

            <?= env('WATCHER_NMS_URL') ?
                $this->Html->link(
                    __('Network Management System'),
                    env('WATCHER_NMS_URL'),
                    ['class' => 'button button-small']
                ) : '' ?>

            <?= $request->getParam('action') == 'index' ? $this->Form->select(
                'limit',
                [
                    $urlWithQuery(['limit' => 20]) => 20,
                    $urlWithQuery(['limit' => 50]) => 50,
                    $urlWithQuery(['limit' => 100]) => 100,
                    $urlWithQuery(['limit' => 500]) => 500,
                    $urlWithQuery(['limit' => 1000]) => 1000,
                    $urlWithQuery(['limit' => 5000]) => 5000,
                    $urlWithQuery(['limit' => 10000]) => 10000,
                ],
                [
                    'value' => $urlWithQuery(['limit' => Configure::read('UI.number_of_rows_per_page')]),
                    'escape' => false,
                    'onchange' => 'location = this.value;',
                    'class' => 'button button-small button-outline',
                ]
            ) : '' ?>

            <?= $this->Form->select(
                'theme',
                [
                    $urlWithQuery(['theme' => 'default']) => __('Default'),
                    $urlWithQuery(['theme' => 'contrast']) => __('Contrast'),
                    $urlWithQuery(['theme' => 'legacy']) => __('Legacy'),
                    $urlWithQuery(['theme' => 'dark']) => __('Dark') . ' (dev)',
                ],
                [
                    'value' => $urlWithQuery(['theme' => Configure::read('UI.theme')]),
                    'escape' => false,
                    'onchange' => 'location = this.value;',
                    'class' => 'button button-small button-outline',
                ]
            ) ?>

            <?= $this->Form->select(
                'language',
                [
                    $urlWithQuery(['language' => 'cs_CZ']) => 'Čeština',
                    $urlWithQuery(['language' => 'en_US']) => 'English',
                ],
                [
                    'value' => $urlWithQuery(['language' => Configure::read('UI.language')]),
                    'escape' => false,
                    'onchange' => 'location = this.value;',
                    'class' => 'button button-small button-outline',
                ]
            ) ?>

            <?= $request->getAttribute('identity') != null ? $this->AuthLink->link(
                __('Logout'),
                ['controller' => 'AppUsers', 'action' => 'logout', 'plugin' => null],
                ['class' => 'button button-small button-outline']
            ) : '' ?>
        </div>
        <?php endif; ?>
    </nav>

    <?= $request->getParam('customer_id') ? $this->cell(
        'Customer',
        [$request->getParam('customer_id')],
        ['compact' => ($request->getQuery('win-link') == 'true')]
    ) : '' ?>

    <main class="main">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <footer>
        <br>
        <div class="container">
            <div class="float-right" title="<?= __('Changelog') . ': ' . PHP_EOL . h(Versioning::getChangelog()) ?>">
                <?= __('Version') . ': ' . h(Versioning::getVersion()) ?>
            </div>
            <br><br>
        </div>
    </footer>
</body>
</html>
