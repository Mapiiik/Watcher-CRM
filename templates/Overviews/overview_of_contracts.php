<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Customer> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $labels
 * @var \Cake\Collection\CollectionInterface|array<string> $accessPoints
 * @var \Cake\Collection\CollectionInterface|array<string> $ruianAddresses
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Overviews'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="overviews form content">
            <h3><?= __('Overview of Contracts') ?></h3>

            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
            <fieldset>
                <?= $this->Form->control('service_type_id', [
                    'empty' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('cto_category', [
                    'empty' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('access_point_id', [
                    'options' => $accessPoints,
                    'empty' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('label_ids', [
                    'label' => __('Labels'),
                    'options' => $labels,
                    'multiple' => 'multiple',
                    'style' => 'height: 100px;',
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('ruian_address_id', [
                    'options' => $ruianAddresses,
                    'empty' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
            </fieldset>
            <?= $this->Form->end() ?>
        </div>
        <hr />
        <div class="customerMessages index content">
            <h4><?= __('Selected Contracts') ?></h4>
            <?php if (!empty($contracts)) : ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= $this->Paginator->sort('customer_id') ?></th>
                            <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                            <th><?= $this->Paginator->sort('number') ?></th>
                            <th><?= $this->Paginator->sort('contract_state_id') ?></th>
                            <th><?= $this->Paginator->sort('service_type_id') ?></th>
                            <th><?= $this->Paginator->sort('installation_address_id') ?></th>
                            <th><?= $this->Paginator->sort('vip') ?></th>
                            <th><?= $this->Paginator->sort('access_point_id') ?></th>
                            <th><?=
                                $this->Paginator->sort(
                                    'installation_date',
                                    __('Installation/Establishment Date'),
                                ) ?></th>
                            <th><?=
                                $this->Paginator->sort(
                                    'uninstallation_date',
                                    __('Uninstallation/Cancellation Date'),
                                ) ?></th>
                            <th><?=
                                $this->Paginator->sort(
                                    'termination_date',
                                    __('Date of Termination of Services'),
                                ) ?></th>
                            <th><?= __('Emails') ?></th>
                            <th><?= __('Phones') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td><?=
                                $contract->__isset('customer') ? $this->Html->link(
                                    $contract->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                                ) : '' ?></td>
                            <td><?= $contract->__isset('customer') ? h($contract->customer->number) : '' ?></td>
                            <td><?= h($contract->number) ?></td>
                            <td><?=
                                $contract->__isset('contract_state') ? $this->Html->link(
                                    $contract->contract_state->name,
                                    [
                                        'controller' => 'ContractStates',
                                        'action' => 'view',
                                        $contract->contract_state->id,
                                    ],
                                ) : '' ?></td>
                            <td><?=
                                $contract->__isset('service_type') ? $this->Html->link(
                                    $contract->service_type->name,
                                    ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id],
                                ) : '' ?></td>
                            <td><?=
                                $contract->__isset('installation_address') ? $this->Html->link(
                                    $contract->installation_address->full_address,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->installation_address->id,
                                    ],
                                ) : '' ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->__isset('access_point') ? h($contract->access_point['name']) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td><?= h($contract->uninstallation_date) ?></td>
                            <td><?= h($contract->termination_date) ?></td>
                            <td><?= implode('<br>', array_column($contract->customer->emails, 'email')) ?></td>
                            <td><?= implode('<br>', array_column($contract->customer->phones, 'phone')) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Contracts', 'action' => 'view', $contract->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Contracts', 'action' => 'edit', $contract->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Contracts', 'action' => 'delete', $contract->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id)],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="paginator">
                <ul class="pagination">
                    <?= $this->Paginator->first('<< ' . __('first')) ?>
                    <?= $this->Paginator->prev('< ' . __('previous')) ?>
                    <?= $this->Paginator->numbers() ?>
                    <?= $this->Paginator->next(__('next') . ' >') ?>
                    <?= $this->Paginator->last(__('last') . ' >>') ?>
                </ul>
                <p><?=
                    $this->Paginator->counter(
                        __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
                    ) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
