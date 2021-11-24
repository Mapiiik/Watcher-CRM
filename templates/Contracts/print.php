<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('View Contract'), ['action' => 'view', $contract->id], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('Edit Contract'), ['action' => 'edit', $contract->id], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contracts form content">
            <h3><?= h($contract->number) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $contract->has('customer') ? $this->Html->link($contract->customer->name, ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <td><?= $contract->has('service_type') ? $this->Html->link($contract->service_type->name, ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <td><?= h($contract->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Address') ?></th>
                            <td><?= $contract->has('installation_address') ? $this->Html->link($contract->installation_address->full_address, ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Date') ?></th>
                            <td><?= h($contract->installation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Technician') ?></th>
                            <td><?= $contract->has('installation_technician') ? $this->Html->link($contract->installation_technician->name, ['controller' => 'Customers', 'action' => 'view', $contract->installation_technician->id]) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Conclusion Date') ?></th>
                            <td><?= h($contract->conclusion_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number Of Amendments') ?></th>
                            <td><?= $this->Number->format($contract->number_of_amendments) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid From') ?></th>
                            <td><?= h($contract->valid_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid Until') ?></th>
                            <td><?= h($contract->valid_until) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Obligation Until') ?></th>
                            <td><?= h($contract->obligation_until) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee') ?></th>
                            <td><?= h($contract->activation_fee) ?><?= $contract->has('service_type') ? ' (' . h($contract->service_type->activation_fee) . ')' : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee With Obligation') ?></th>
                            <td><?= h($contract->activation_fee_with_obligation) ?><?= $contract->has('service_type') ? ' (' . h($contract->service_type->activation_fee_with_obligation) . ')' : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <br />
            <?= $this->Form->create($contract, [
                'type' => 'get',
                'valueSources' => ['query', 'context'],
                'target' => 'print',
                'url' => [
                    'action' => 'print',
                    $contract->id,
                    '_ext' => 'pdf'
                ]
            ]) ?>
            <fieldset>
                <legend><?= __('Print Documents') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('document_type', ['label' => __('Document Type'), 'options' => $documentTypes, 'empty' => true, 'required' => true]);
                        echo $this->Form->control('signed', ['label' => __('Signed'), 'type' => 'checkbox']);
                    ?>
                    </div>
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('effective_date_of_the_amendment', ['label' => __('Effective date of the amendment'), 'empty' => true, 'type' => 'date']);
                        echo $this->Form->control('number_of_the_contract_to_be_terminated', ['label' => __('The number of the contract to be terminated'), 'empty' => true]);
                        echo $this->Form->control('ssid', ['label' => __('SSID'), 'empty' => true]);
                        echo $this->Form->control('radius_username', ['empty' => true]);
                        echo $this->Form->control('radius_password', ['empty' => true]);
                    ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
