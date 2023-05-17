<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerLabel $customerLabel
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Customer Label'),
                ['action' => 'edit', $customerLabel->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Customer Label'),
                ['action' => 'delete', $customerLabel->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $customerLabel->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Customer Labels'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Customer Label'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerLabels view content">
            <h3><?= h($customerLabel->id) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $customerLabel->has('customer') ? $this->Html->link(
                                $customerLabel->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $customerLabel->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $customerLabel->has('customer') ? h($customerLabel->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Label') ?></th>
                            <td><?= $customerLabel->has('label') ? $this->Html->link(
                                $customerLabel->label->name,
                                ['controller' => 'Labels', 'action' => 'view', $customerLabel->label->id]
                            ) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($customerLabel->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($customerLabel->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $customerLabel->has('creator') ? $this->Html->link(
                                $customerLabel->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $customerLabel->creator->id,
                                ]
                            ) : h($customerLabel->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($customerLabel->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $customerLabel->has('modifier') ? $this->Html->link(
                                $customerLabel->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $customerLabel->modifier->id,
                                ]
                            ) : h($customerLabel->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($customerLabel->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
