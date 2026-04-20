<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Phone $phone
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Phone'),
                ['action' => 'edit', $phone->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Phone'),
                ['action' => 'delete', $phone->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $phone->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Phones'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Phone'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="phones view content">
            <h3><?= h($phone->phone) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $phone->customer !== null ? $this->Html->link(
                                $phone->customer->name ?? '(' . $phone->customer->id . ')',
                                ['controller' => 'Customers', 'action' => 'view', $phone->customer->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $phone->customer !== null ? h($phone->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Phone') ?></th>
                            <td><?= h($phone->phone) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Use For Billing') ?></th>
                            <td><?= $phone->use_for_billing ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Use For Outages') ?></th>
                            <td><?= $phone->use_for_outages ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Use For Commercial') ?></th>
                            <td><?= $phone->use_for_commercial ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $phone]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($phone->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
