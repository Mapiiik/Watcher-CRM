<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpNetwork $ipNetwork
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Ip Network'),
                ['action' => 'edit', $ipNetwork->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Ip Network'),
                ['action' => 'delete', $ipNetwork->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ipNetwork->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __('List Ip Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __('New Ip Network'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="ipNetworks view content">
            <h3><?= h($ipNetwork->ip_network) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $ipNetwork->has('customer') ?
                        $this->Html->link(
                            $ipNetwork->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $ipNetwork->customer->id]
                        ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $ipNetwork->has('contract') ?
                        $this->Html->link(
                            $ipNetwork->contract->name,
                            ['controller' => 'Contracts', 'action' => 'view', $ipNetwork->contract->id]
                        ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Ip Network') ?></th>
                    <td><?= h($ipNetwork->ip_network) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type Of Use') ?></th>
                    <td><?= h($types_of_use[$ipNetwork->type_of_use]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($ipNetwork->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($ipNetwork->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($ipNetwork->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($ipNetwork->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($ipNetwork->modified_by) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($ipNetwork->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
