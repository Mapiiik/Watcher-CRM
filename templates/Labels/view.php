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
            <?= $this->Html->link(__('Edit Label'), ['action' => 'edit', $label->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Label'), ['action' => 'delete', $label->id], ['confirm' => __('Are you sure you want to delete # {0}?', $label->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Labels'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Label'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="labels view content">
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
                    <td><?= h($label->color) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($label->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Validity') ?></th>
                    <td><?= $this->Number->format($label->validity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Dynamic') ?></th>
                    <td><?= $label->dynamic ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Dynamic Sql') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($label->dynamic_sql)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Label Customers') ?></h4>
                <?php if (!empty($label->label_customers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Label Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($label->label_customers as $labelCustomers) : ?>
                        <tr>
                            <td><?= h($labelCustomers->label_id) ?></td>
                            <td><?= h($labelCustomers->customer_id) ?></td>
                            <td><?= h($labelCustomers->created) ?></td>
                            <td><?= h($labelCustomers->note) ?></td>
                            <td><?= h($labelCustomers->id) ?></td>
                            <td><?= h($labelCustomers->created_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'LabelCustomers', 'action' => 'view', $labelCustomers->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'LabelCustomers', 'action' => 'edit', $labelCustomers->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'LabelCustomers', 'action' => 'delete', $labelCustomers->id], ['confirm' => __('Are you sure you want to delete # {0}?', $labelCustomers->id)]) ?>
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
