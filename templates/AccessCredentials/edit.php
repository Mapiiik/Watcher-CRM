<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AccessCredential $accessCredential
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $accessCredential->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $accessCredential->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Access Credentials'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="accessCredentials form content">
            <?= $this->Form->create($accessCredential) ?>
            <fieldset>
                <legend><?= __('Edit Access Credential') ?></legend>
                <?php
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
                if (isset($accessCredential->customer_id)) {
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
                $this->Form->unlockField('refresh'); //disable form security check
                ?>
                <hr>
                <?php
                echo $this->Form->control('name');
                echo $this->Form->control('username');
                echo $this->Form->control('password', ['type' => 'text']);
                echo $this->Form->control('ip_address', ['label' => __('IP Address')]);
                echo $this->Form->control('port');
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
