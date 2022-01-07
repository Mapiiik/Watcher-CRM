<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Billing $billing
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Billings'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="billings form content">
            <?= $this->Form->create($billing) ?>
            <fieldset>
                <legend><?= __('Add Billing') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', [
                        'options' => $contracts,
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
                    $this->Form->unlockField('refresh'); //disable form security check
                }
                echo $this->Form->control('service_id', ['options' => $services, 'empty' => true]);
                echo $this->Form->control('text');
                echo $this->Form->control('quantity');
                echo $this->Form->control('price');
                echo $this->Form->control('fixed_discount');
                echo $this->Form->control('percentage_discount');
                echo $this->Form->control('billing_from', ['empty' => true]);
                echo $this->Form->control('billing_until', ['empty' => true]);
                echo $this->Form->control('active');
                echo $this->Form->control('separate_invoice');
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
