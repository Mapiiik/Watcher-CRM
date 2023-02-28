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
 * @var \App\View\AppView $this
 */

use App\Controller\AppController;

$cakeDescription = 'Watcher CRM | Legacy UI | ' . env('APP_COMPANY', 'ISP');
$request = $this->getRequest();
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

    <?= $this->Html->script(['https://code.jquery.com/jquery.min.js', 'links.js']) ?>

    <?php if (filter_var(env('ENABLE_SELECT2', false), FILTER_VALIDATE_BOOLEAN)) : ?>
        <?= $this->Html->css(['https://cdn.jsdelivr.net/npm/select2@4.0/dist/css/select2.min.css']) ?>
        <?= $this->Html->script([
            'https://cdn.jsdelivr.net/npm/select2@4.0/dist/js/select2.min.js',
            'select2-settings.js',
        ]) ?>
    <?php endif ?>

    <?= $this->Html->css(['normalize.min', 'legacy']) ?>
    <?= $request->getSession()->read('Config.high-contrast') ? $this->Html->css(['high-contrast']) : '' ?>


    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-title">
            <a href="<?= $this->Url->build('/') ?>"><span>Watcher</span> CRM</a>
        </div>

        <?php if (!($request->getQuery('win-link') == 'true')) : ?>
        <div class="top-nav-links to-right">
            <?php
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
            }; ?>

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
                    'Users',
                ])]
            ) ?>

            <?= $this->Html->link(__('Default UI'), '/admin', ['class' => 'button button-small']) ?>

            <?= env('WATCHER_NMS_URL') ?
                $this->Html->link(
                    __('Network Management System'),
                    env('WATCHER_NMS_URL'),
                    ['class' => 'button button-small']
                ) : '' ?>

            <?php if ($request->getParam('action') == 'index') : ?>
            <select name="limit" class="button button-small button-outline" onchange="location = this.value;">
                <option <?= $request->getSession()->read('Config.limit') == 20 ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['limit' => 20]) ?>">20</option>
                <option <?= $request->getSession()->read('Config.limit') == 50 ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['limit' => 50]) ?>">50</option>
                <option <?= $request->getSession()->read('Config.limit') == 100 ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['limit' => 100]) ?>">100</option>
                <option <?= $request->getSession()->read('Config.limit') == 500 ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['limit' => 500]) ?>">500</option>
                <option <?= $request->getSession()->read('Config.limit') == 1000 ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['limit' => 1000]) ?>">1000</option>
                <option <?= $request->getSession()->read('Config.limit') == 5000 ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['limit' => 5000]) ?>">5000</option>
                <option <?= $request->getSession()->read('Config.limit') == 10000 ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['limit' => 10000]) ?>">10000</option>
            </select>
            <?php endif; ?>
            
            <?php
            $language = $request->getSession()->read('Config.language', Cake\I18n\I18n::getDefaultLocale());
            ?>
            <select name="language" class="button button-small button-outline" onchange="location = this.value;">
                <option <?= $language == 'cs_CZ' ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['language' => 'cs_CZ']) ?>">Čeština</option>
                <option <?= $language == 'en_US' ? 'selected="selected"' : '' ?>
                    value="<?= $urlWithQuery(['language' => 'en_US']) ?>">English</option>
            </select>

            <?= $this->getRequest()->getAttribute('identity') != null ? $this->AuthLink->link(
                __('Logout'),
                ['controller' => 'Users', 'action' => 'logout', 'plugin' => 'CakeDC/Users'],
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
            <div class="float-right" title="<?= __('Changelog') . ': ' . PHP_EOL . h(AppController::getChangelog()) ?>">
                <?= __('Version') . ': ' . h(AppController::getVersion()) ?>
            </div>
            <br><br>
            <div class="float-right">
            <?= $this->Form->create(null, ['type' => 'get']) ?>
                <?= $this->Form->control('high-contrast', [
                    'label' => __('High Contrast'),
                    'type' => 'checkbox',
                    'checked' => $request->getSession()->read('Config.high-contrast'),
                    'onchange' => 'this.form.submit();',
                ]) ?>
            <?= $this->Form->end() ?>
            </div>
        </div>
    </footer>
</body>
</html>
