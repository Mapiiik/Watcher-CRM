<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Label $label
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Label'),
                ['action' => 'edit', $label->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Label'),
                ['action' => 'delete', $label->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $label->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Labels'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Label'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="labels view content">
            <?= $this->Form->postLink(
                __('Update Related Customer Labels'),
                ['action' => 'updateRelatedCustomerLabels', $label->id],
                [
                    'confirm' => __('Are you sure you want to update related customer labels # {0}?', $label->id),
                    'class' => 'button float-right',
                ]
            ) ?>
            <h3><?= h($label->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($label->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Caption') ?></th>
                    <td><?= h($label->caption) ?></td>
                </tr>
                <tr>
                    <th><?= __('Color') ?></th>
                    <td style="background-color: <?= h($label->color) ?>;"><?= h($label->color) ?></td>
                </tr>
                <tr>
                    <th><?= __('Validity') ?></th>
                    <td><?= $this->Number->format($label->validity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Dynamic') ?></th>
                    <td><?= $label->dynamic ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($label->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($label->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $label->has('creator') ? $this->Html->link(
                        $label->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $label->creator->id,
                        ]
                    ) : h($label->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($label->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $label->has('modifier') ? $this->Html->link(
                        $label->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $label->modifier->id,
                        ]
                    ) : h($label->modified_by) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Dynamic Sql') ?></strong>
                <?= SqlFormatter::format($label->dynamic_sql); ?>
            </div>
            <div class="related">
                <h4><?= __('Related Customer Labels') ?></h4>
                <?php if (!empty($label->customer_labels)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Created') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($label->customer_labels as $customerLabel) : ?>
                        <tr>
                            <td><?= $customerLabel->has('customer') ?
                                $this->Html->link(
                                    $customerLabel->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $customerLabel->customer->id]
                                ) : '' ?></td>
                            <td><?= h($customerLabel->note) ?></td>
                            <td><?= h($customerLabel->created) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'CustomerLabels', 'action' => 'view', $customerLabel->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'CustomerLabels', 'action' => 'edit', $customerLabel->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'CustomerLabels', 'action' => 'delete', $customerLabel->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $customerLabel->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
