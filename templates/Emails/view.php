<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Email $email
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Email'),
                ['action' => 'edit', $email->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Email'),
                ['action' => 'delete', $email->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $email->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Emails'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Email'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="emails view content">
            <h3><?= h($email->email) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $email->customer !== null ? $this->Html->link(
                                $email->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $email->customer->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $email->customer !== null ? h($email->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Email') ?></th>
                            <td><?= $this->Html->link(h($email->email), 'mailto:' . $email->email) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Use For Billing') ?></th>
                            <td><?= $email->use_for_billing ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Use For Outages') ?></th>
                            <td><?= $email->use_for_outages ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Use For Commercial') ?></th>
                            <td><?= $email->use_for_commercial ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $email]) ?>
                </div>
            </div>
        <div class="text">
            <strong><?= __('Note') ?></strong>
            <blockquote>
                <?= $this->Text->autoParagraph(h($email->note)); ?>
            </blockquote>
        </div>
        </div>
    </div>
</div>
