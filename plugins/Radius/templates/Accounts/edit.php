<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Account $account
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Account'),
                ['action' => 'delete', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $account->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Accounts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="accounts form content">
            <?= $this->Form->create($account) ?>
            <fieldset>
                <legend><?= __d('radius', 'Edit RADIUS Account') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', [
                        'label' => __d('radius', 'Customer'),
                        'options' => $customers,
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
                        'label' => __d('radius', 'Contract'),
                        'empty' => true,
                        'options' => $contracts,
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
                $this->Form->unlockField('refresh'); //disable form security check

                echo $this->Form->control('username', [
                    'label' => __d('radius', 'Username'),
                    'readonly' => true,
                    'type' => 'text',
                ]);
                echo $this->Form->control('password', [
                    'label' => __d('radius', 'Password'),
                    'type' => 'text',
                ]);
                echo $this->Form->control('active', ['label' => __d('radius', 'Active')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
