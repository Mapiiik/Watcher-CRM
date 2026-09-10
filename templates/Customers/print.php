<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Form\Form $printForm
 * @var \App\Model\Enum\CustomerPrintType|null $printType
 * @var \App\Model\Entity\Customer $customer
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $documentTypes
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Customer'),
                ['action' => 'view', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Edit Customer'),
                ['action' => 'edit', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Customer Documents'),
                ['action' => 'documents', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= $this->element('Customers/heading') ?>
            <?= $this->element('Customers/facts') ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Proposal'),
                    [
                        'controller' => 'CustomerProposals',
                        'action' => 'add',
                        'customer_id' => $customer->id,
                    ],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Proposals') ?></h4>
                <?= $this->element('Customers/CustomerProposals', [
                    'customer_proposals' => $customer->customer_proposals ?? [],
                ]) ?>
            </div>
            <br>
            <?= $this->Form->create($printForm, [
                'type' => 'get',
                'valueSources' => ['query'],
                'url' => [
                    'action' => 'print',
                    $customer->id,
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Print Documents') ?></legend>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('document_type', [
                            'label' => __('Document Type'),
                            'options' => $documentTypes,
                            'empty' => true,
                            'required' => true,
                            'onchange' => $this::SUBMIT_ON_CHANGE,
                        ]);
                        ?>
                    </div>
                    <div class="column">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->hidden('submit_action', [
                'value' => 'refresh',
            ]) ?>
            <?= $this->Form->button(__('Print to PDF'), [
                'name' => 'submit_action',
                'value' => 'pdf',
            ]) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
