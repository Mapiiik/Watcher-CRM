<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Billing $billing
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $customers
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $services
 * @var bool|null $closed_period_override
 * @var bool|null $invoiced_for
 * @var bool|null $end_invoiced_for
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $billing->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Billings'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="billings form content">
            <?= $this->Form->create($billing) ?>
            <fieldset>
                <legend><?= __('Edit Billing') ?></legend>
                <?php if (!empty($invoiced_for)) : ?>
                <div class="message warning" role="alert">
                    <?= __('This billing has been invoiced for, so what it charges is no longer changed here.') ?>
                    <?php // only a way out while the billing still has an end to give ?>
                    <?php if (empty($end_invoiced_for)) : ?>
                        <?= __('End it and start a new one from the day the change takes effect:') ?>
                        <?= $this->AuthLink->link(
                            __('Service Change'),
                            ['action' => 'serviceChange', $billing->id],
                        ) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="row">
                    <div class="column">
                        <?php
                        if (!isset($customer_id)) {
                            echo $this->Form->control('customer_id', ['options' => $customers]);
                        }
                        if (!isset($contract_id)) {
                            echo $this->Form->control('contract_id', [
                                'options' => $contracts,
                                'empty' => true,
                                'onchange' => $this::REFRESH_ON_CHANGE,
                            ]);
                            $this->Form->unlockField('refresh'); //disable form security check
                        }
                        // what is charged, and the day the charging started, are settled once an
                        // invoice has gone out - and a box that cannot be saved is better shut
                        // than argued with afterwards. The end is asked separately below: a
                        // billing running on may still be brought to a close.
                        $settled = ['disabled' => !empty($invoiced_for) && empty($closed_period_override)];
                        echo $this->Form->control('service_id', $settled + [
                            'options' => $services,
                            'empty' => true,
                        ]);
                        echo $this->Form->control('text');
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('quantity', $settled);
                        echo $this->Form->control('price', $settled);
                        echo $this->Form->control('fixed_discount', $settled);
                        echo $this->Form->control('percentage_discount', $settled);
                        ?>
                    </div>
                </div>
                <?php
                echo $this->Form->control('billing_from', $settled + ['empty' => true]);
                echo $this->Form->control('billing_until', [
                    'empty' => true,
                    'disabled' => !empty($end_invoiced_for) && empty($closed_period_override),
                ]);
                echo $this->Form->control('separate_invoice');
                echo $this->Form->control('note');
                // last of all, right above the button: reaching into an invoiced period is the
                // deliberate act, not the one made on the way past the dates
                if (!empty($closed_period_override)) {
                    echo $this->Form->control('allow_closed_periods', [
                        'type' => 'checkbox',
                        'label' => __('Allow a change inside an already invoiced period'),
                    ]);
                }
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
