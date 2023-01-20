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

    <link href="https://fonts.googleapis.com/css?family=Raleway:400,700" rel="stylesheet">

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
