<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Address $address
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Address'), ['action' => 'edit', $address->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Address'), ['action' => 'delete', $address->id], ['confirm' => __('Are you sure you want to delete # {0}?', $address->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Address'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="addresses view content">
            <h3><?= h($address->title) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $address->has('customer') ? $this->Html->link($address->customer->title, ['controller' => 'Customers', 'action' => 'view', $address->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Country') ?></th>
                    <td><?= $address->has('country') ? $this->Html->link($address->country->name, ['controller' => 'Countries', 'action' => 'view', $address->country->]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($address->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type') ?></th>
                    <td><?= $this->Number->format($address->type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($address->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($address->modified_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Ruian Gid') ?></th>
                    <td><?= $this->Number->format($address->ruian_gid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Gpsx') ?></th>
                    <td><?= $this->Number->format($address->gpsx) ?></td>
                </tr>
                <tr>
                    <th><?= __('Gpsy') ?></th>
                    <td><?= $this->Number->format($address->gpsy) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($address->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($address->modified) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Title') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->title)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('First Name') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->first_name)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Last Name') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->last_name)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Suffix') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->suffix)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Company') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->company)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Street') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->street)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Number') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->number)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('City') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->city)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Zip') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->zip)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
