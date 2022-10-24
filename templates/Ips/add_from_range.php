<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ip $ip
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 * @var string[]|\Cake\Collection\CollectionInterface $contracts
 * @var string[]|\Cake\Collection\CollectionInterface $types_of_use
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="ips form content">
            <?= $this->Form->create($ip) ?>
            <fieldset>
                <legend><?= __('Add IP Address From Range') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', [
                        'options' => $customers,
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
                }
                echo $this->Form->control('type_of_use', [
                    'options' => $types_of_use,
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

                echo $this->Form->control('ip', [
                    'empty' => true,
                ]);
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
