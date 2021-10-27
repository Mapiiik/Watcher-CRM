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

$cakeDescription = 'Watcher CRM | ' . env('APP_COMPANY', 'ISP');
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

    <?= $this->Html->css(['normalize.min', 'milligram.min', 'cake']) ?>

    <?= $this->Html->script('https://code.jquery.com/jquery.min.js') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
<?php if (!isset($_SERVER['HTTP_REFERER']) || (strpos($_SERVER['HTTP_REFERER'], '/legacy/administration/') === false)): ?>
    <nav class="top-nav">
        <div class="top-nav-title">
            <a href="<?= $this->Url->build('/') ?>"><span>Watcher</span> CRM</a>
        </div>

        <div class="top-nav-links">
            <?php
            $controller = $this->name;
            $action = $this->request->getParam('action');
            $buttonSelected = function ($haystack = []) use ($controller, $action)
            {
                if (in_array($controller, $haystack))
                    return ' button-selected';
                else if (in_array($action, $haystack))
                    return ' button-selected';
                else                    
                    return '';
            };
            ?>
            <?= $this->AuthLink->link(__('Customers'), ['plugin' => null, 'controller' => 'Customers', 'action' => 'index'], ['class' => 'button button-small' . $buttonSelected(['Customers'])]) ?>
            <?= $this->AuthLink->link(__('Tasks'), ['plugin' => null, 'controller' => 'Tasks', 'action' => 'index'], ['class' => 'button button-small' . $buttonSelected(['Tasks'])]) ?>
            <?= $this->AuthLink->link(__('Bookkeeping'), ['plugin' => 'BookkeepingPohoda', 'controller' => 'Invoices', 'action' => 'index'], ['class' => 'button button-small' . $buttonSelected(['Invoices'])]) ?>
            <?= $this->AuthLink->link(__('RADIUS'), ['plugin' => 'RADIUS', 'controller' => 'Accounts', 'action' => 'index'], ['class' => 'button button-small' . $buttonSelected(['Accounts', 'Nass', 'Radacct', 'Radcheck', 'Radgroupcheck', 'Radgroupreply', 'Radpostauth', 'Radreply', 'Radusergroup'])]) ?>
            <?= $this->AuthLink->link(__('RUIAN'), ['plugin' => 'RUIAN', 'controller' => 'Addresses', 'action' => 'index'], ['class' => 'button button-small' . $buttonSelected(['Addresses'])]) ?>
            <?= $this->AuthLink->link(__('Users'), ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'index'], ['class' => 'button button-small' . $buttonSelected(['Users'])]) ?>
            
            <?php if (!is_null($this->request->getSession()->read('Auth.id'))) echo $this->Html->link(__('Legacy'), '/legacy', ['class' => 'button button-small']); ?>

            <?php if ($this->request->getParam('action') == 'index'): ?>
            <select name="limit" class="button button-small button-outline" onchange="location = this.value;">
                <option <?php if ($this->request->getQuery('limit') == 20) echo 'selected="selected"'; ?> value="<?php echo $this->Url->build(['?' => ['limit' => 20] + $this->request->getQueryParams()] + $this->request->getParam('pass')); ?>">20</option>
                <option <?php if ($this->request->getQuery('limit') == 50) echo 'selected="selected"'; ?> value="<?php echo $this->Url->build(['?' => ['limit' => 50] + $this->request->getQueryParams()] + $this->request->getParam('pass')); ?>">50</option>
                <option <?php if ($this->request->getQuery('limit') == 100) echo 'selected="selected"'; ?> value="<?php echo $this->Url->build(['?' => ['limit' => 100] + $this->request->getQueryParams()] + $this->request->getParam('pass')); ?>">100</option>
                <option <?php if ($this->request->getQuery('limit') == 500) echo 'selected="selected"'; ?> value="<?php echo $this->Url->build(['?' => ['limit' => 500] + $this->request->getQueryParams()] + $this->request->getParam('pass')); ?>">500</option>
                <option <?php if ($this->request->getQuery('limit') == 1000) echo 'selected="selected"'; ?> value="<?php echo $this->Url->build(['?' => ['limit' => 1000] + $this->request->getQueryParams()] + $this->request->getParam('pass')); ?>">1000</option>
            </select>
            <?php endif; ?>
            
            <?php $language = $this->request->getSession()->read('Config.language', Cake\I18n\I18n::getDefaultLocale()); ?>
            
            <select name="language" class="button button-small button-outline" onchange="location = this.value;">
                <option <?php if ($language == 'cs_CZ') echo 'selected="selected"'; ?> value="<?php echo $this->Url->build(['?' => ['language' => 'cs_CZ'] + $this->request->getQueryParams()] + $this->request->getParam('pass')); ?>">Čeština</option>
                <option <?php if ($language == 'en_US') echo 'selected="selected"'; ?> value="<?php echo $this->Url->build(['?' => ['language' => 'en_US'] + $this->request->getQueryParams()] + $this->request->getParam('pass')); ?>">English</option>
            </select>

            <?php if (!is_null($this->request->getSession()->read('Auth.id'))) echo $this->AuthLink->link(__('Logout'), ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'logout'], ['class' => 'button button-small button-outline']) ?>
        </div>

    </nav>

    <?= $this->request->getParam('customer_id') ? $this->cell('Customer') : ''; ?>

<?php endif; ?>
    <main class="main">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <footer>
    </footer>
</body>
</html>
