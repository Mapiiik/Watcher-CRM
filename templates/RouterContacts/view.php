<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RouterContact $routerContact
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Router Contact'), ['action' => 'edit', $routerContact->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Router Contact'), ['action' => 'delete', $routerContact->id], ['confirm' => __('Are you sure you want to delete # {0}?', $routerContact->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Router Contacts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Router Contact'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="routerContacts view content">
            <h3><?= h($routerContact->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Router') ?></th>
                    <td><?= $routerContact->has('router') ? $this->Html->link($routerContact->router->name, ['controller' => 'Routers', 'action' => 'view', $routerContact->router->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $routerContact->has('customer') ? $this->Html->link($routerContact->customer->title, ['controller' => 'Customers', 'action' => 'view', $routerContact->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($routerContact->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
