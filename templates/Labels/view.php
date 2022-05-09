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
                <h4><?= __('Related Customer Labels') ?></h4>
                <?php if (!empty($label->customer_labels)) : ?>
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
                        <?php foreach ($label->customer_labels as $customerLabels) : ?>
                        <tr>
                            <td><?= h($customerLabels->label_id) ?></td>
                            <td><?= h($customerLabels->customer_id) ?></td>
                            <td><?= h($customerLabels->created) ?></td>
                            <td><?= h($customerLabels->note) ?></td>
                            <td><?= h($customerLabels->id) ?></td>
                            <td><?= h($customerLabels->created_by) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'CustomerLabels', 'action' => 'view', $customerLabels->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'CustomerLabels', 'action' => 'edit', $customerLabels->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'CustomerLabels', 'action' => 'delete', $customerLabels->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $customerLabels->id)]
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
