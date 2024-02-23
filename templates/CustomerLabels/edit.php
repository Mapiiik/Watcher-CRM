<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerLabel $customerLabel
 * @var \Cake\Collection\CollectionInterface|array<string> $labels
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
                ['action' => 'delete', $customerLabel->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $customerLabel->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Customer Labels'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerLabels form content">
            <?= $this->Form->create($customerLabel) ?>
            <fieldset>
                <legend><?= __('Edit Customer Label') ?></legend>
                <?php
                echo $this->Form->control('label_id', ['options' => $labels]);
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
                    $this->Form->unlockField('refresh'); //disable form security check
                }
                echo $this->Form->control('contract_id', ['options' => $contracts]);
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
