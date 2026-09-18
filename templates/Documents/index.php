<?php
/**
 * The register: what has been drawn up and what state it is in.
 *
 * Deliberately thinner than the workbench. One row to a proposal and nothing about the papers
 * themselves - what somebody is looking for here is whether something has gone out or come back,
 * and the way on to the papers is one link away on every row.
 *
 * @var \App\View\AppView $this
 * @var array<array<string, mixed>> $rounds
 * @var \Cake\Datasource\Paging\PaginatedInterface<array-key, mixed> $paginated
 * @var bool $show_settled
 * @var bool $showCustomer
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => $this::SUBMIT_ON_CHANGE,
            'help' => __('The number of the customer or of a contract, or anything out of the'
                . ' note.'),
        ]) ?>
        <?= $this->Form->control('show_settled', [
            'label' => __('Show settled proposals'),
            'type' => 'checkbox',
            'checked' => $show_settled,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="documents index content">
    <?= $this->AuthLink->link(
        __('New Customer Proposal'),
        ['controller' => 'CustomerProposals', 'action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__('Documents')) ?>
    <p><?= __('Every proposal, and what state it is in. The papers themselves are one link'
        . ' away.') ?></p>
    <?= $this->element('Documents/rounds', ['working' => false, 'paged' => true]) ?>
    <?= $this->element('common/paginator') ?>
</div>
