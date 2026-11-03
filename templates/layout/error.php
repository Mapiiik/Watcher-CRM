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
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?php // the ICO is for those that cannot take the SVG, and its size keeps those that can on the SVG ?>
    <?= $this->Html->meta('icon', 'favicon.ico', ['sizes' => '32x32']) ?>
    <?= $this->Html->meta('icon', 'favicon.svg', ['type' => 'image/svg+xml']) ?>
    <?= $this->Html->meta(['rel' => 'apple-touch-icon', 'link' => 'apple-touch-icon.png']) ?>

    <?= $this->Html->css([
        '/vendor/normalize/normalize.min.css',
        '/vendor/milligram/milligram.min.css',
        'cake',
    ]) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <div class="error-container">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
        <?= $this->Html->link(__('Back'), 'javascript:history.back()') ?>
    </div>
</body>
</html>
