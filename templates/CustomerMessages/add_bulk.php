<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerMessage $customerMessage
 * @var iterable<\App\Model\Entity\Customer> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $labels
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Customer Messages'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerMessages form content">
            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
            <fieldset>
                <legend><?= __('Customers to Receive The Message') ?></legend>
                <?= $this->Form->control('label_id', [
                    'options' => $labels,
                    'empty' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
            </fieldset>
            <?= $this->Form->end() ?>
            
            <?= $this->Form->create($customerMessage) ?>
            <fieldset>
                <legend><?= __('Add Bulk Customer Message') ?></legend>
                <?php
                    echo $this->Form->control('type');
                    echo $this->Form->control('subject');
                    echo $this->Form->control('body');
                    echo $this->Form->control('body_format');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
        <hr />
        <div class="customerMessages index content">
            <h4><?= __('Selected Customers') ?></h4>
            <?php if (!empty($customers)) : ?>
            <div class="table-responsive">
                <table>
                    <tr>
                        <th><?= __('Customer') ?></th>
                        <th><?= __('Customer Number') ?></th>
                        <th><?= __('Emails') ?></th>
                        <th><?= __('Phones') ?></th>
                    </tr>
                    <?php foreach ($customers as $customer) : ?>
                    <tr>
                        <td><?= $this->Html->link($customer->name, [
                            'controller' => 'Customers',
                            'action' => 'view',
                            $customer->id,
                        ]) ?></td>
                        <td><?= h($customer->number) ?></td>
                        <td><?= implode('<br>', array_column($customer->emails, 'email')) ?></td>
                        <td><?= implode('<br>', array_column($customer->phones, 'phone')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
