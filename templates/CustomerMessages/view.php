<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerMessage $customerMessage
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Customer Message'),
                ['action' => 'edit', $customerMessage->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Customer Message'),
                ['action' => 'delete', $customerMessage->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $customerMessage->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Customer Messages'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Customer Message'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Bulk Customer Message'),
                ['action' => 'add-bulk'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerMessages view content">
            <h3><?= h($customerMessage->subject) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $customerMessage->hasValue('customer') ?
                                $this->AuthLink->link(
                                    $customerMessage->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $customerMessage->customer->id]
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Recipients') ?></th>
                            <td><?= implode('<br>', $customerMessage->recipients) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Subject') ?></th>
                            <td><?= h($customerMessage->subject) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type') ?></th>
                        <td><?= h($customerMessage->type->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Direction') ?></th>
                        <td><?= h($customerMessage->direction->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Body Format') ?></th>
                        <td><?= h($customerMessage->body_format->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Delivery Status') ?></th>
                        <td><?= h($customerMessage->delivery_status->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Processed') ?></th>
                            <td><?= h($customerMessage->processed) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Identifier') ?></th>
                            <td><?= h($customerMessage->identifier) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($customerMessage->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($customerMessage->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $customerMessage->__isset('creator') ? $this->Html->link(
                                $customerMessage->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $customerMessage->creator->id,
                                ]
                            ) : h($customerMessage->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($customerMessage->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $customerMessage->__isset('modifier') ? $this->Html->link(
                                $customerMessage->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $customerMessage->modifier->id,
                                ]
                            ) : h($customerMessage->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Body') ?></strong>
                <pre><?= $this->Text->autoParagraph(h($customerMessage->body)); ?></pre>
            </div>
            <div class="text">
                <strong><?= __('Attachments') ?></strong>
                <pre><?= $customerMessage->__isset('attachments') ?
                    h(json_encode(
                        $customerMessage->attachments,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                    )) : '' ?></pre>
            </div>
        </div>
    </div>
</div>
