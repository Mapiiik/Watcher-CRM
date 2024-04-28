<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpAddress $ipAddress
 * @var iterable<\App\Model\Entity\IpAddress> $ipAddresses
 * @var \Cake\Collection\CollectionInterface|array<string> $accessPoints
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List IP Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ipAddresses form content">
            <?= $this->Form->create($ipAddress) ?>
            <fieldset>
                <legend><?= __('Bulk IP Address Reassignment') ?></legend>
                <?php
                echo $this->Form->control('access_point_id', [
                    'empty' => true,
                    'onchange' => '
                        var refresh = document.createElement("input");
                        refresh.type = "hidden";
                        refresh.name = "refresh";
                        refresh.value = "refresh";
                        this.form.appendChild(refresh);
                        this.form.submit();
                    ',
                ]);
                echo $this->Form->control('type_of_use', [
                    'onchange' => '
                        var refresh = document.createElement("input");
                        refresh.type = "hidden";
                        refresh.name = "refresh";
                        refresh.value = "refresh";
                        this.form.appendChild(refresh);
                        this.form.submit();
                    ',
                ]);
                echo $this->Form->control('ip_address_range', [
                    'label' => __('IP Address Range'),
                    'empty' => true,
                    'required' => true,
                    'onchange' => '
                        var refresh = document.createElement("input");
                        refresh.type = "hidden";
                        refresh.name = "refresh";
                        refresh.value = "refresh";
                        this.form.appendChild(refresh);
                        this.form.submit();
                    ',
                ]);
                $this->Form->unlockField('refresh'); //disable form security check
                ?>
            </fieldset>

            <div class="related">
                <?php if (!empty($ipAddresses)) : ?>
                <h4><?= __('IP Addresses') ?></h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= __('Customer') ?></th>
                                <th><?= __('Customer Number') ?></th>
                                <th><?= __('Contract') ?></th>
                                <th><?= __('IP Address') ?></th>
                                <th><?= __('Reassigned IP Address') ?></th>
                                <th><?= __('Type Of Use') ?></th>
                                <th class="actions"><?= __('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ipAddresses as $ipAddress) : ?>
                            <tr style="<?= $ipAddress->style ?>">
                                <td>
                                    <?= $ipAddress->__isset('customer') ? $this->Html->link(
                                        $ipAddress->customer->name,
                                        ['controller' => 'Customers', 'action' => 'view', $ipAddress->customer->id]
                                    ) : '' ?>
                                </td>
                                <td><?= $ipAddress->__isset('customer') ? h($ipAddress->customer->number) : '' ?></td>
                                <td>
                                    <?= $ipAddress->__isset('contract') ? $this->Html->link(
                                        $ipAddress->contract->name ?? '--',
                                        [
                                            'controller' => 'Contracts',
                                            'action' => 'view',
                                            'customer_id' => $ipAddress->contract->customer_id,
                                            $ipAddress->contract->id,
                                        ]
                                    ) : '' ?>
                                </td>
                                <td><?= h($ipAddress->ip_address) ?></td>
                                <td><?= h($ipAddress['reassigned_ip_address']) ?></td>
                                <td><?= h($ipAddress->type_of_use->label()) ?></td>
                                <td class="actions">
                                    <?= $this->Form->control('reassing_ip_address.' . $ipAddress->id, [
                                        'label' => __('Reassign IP Address'),
                                        'type' => 'checkbox',
                                        'value' => $ipAddress->id,
                                    ]) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
