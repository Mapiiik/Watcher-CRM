<?php
/**
 * The workbench: the papers of a customer and of their contracts, and everything that leads
 * anywhere from them.
 *
 * Cards down the page in the order the work goes: who this is, what has been drawn up, and then
 * the papers themselves - the tables last, so they do not stand between the heading and the doing.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \App\Model\Entity\Contract|null $contract
 * @var \App\Model\Entity\ContractVersion|null $version
 * @var \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round
 * @var array<array<string, mixed>> $rounds
 * @var array{0: string, 1: string} $scope
 * @var string $about The innermost thing the address names, which is what the papers are of.
 * @var bool $with_contracts
 * @var bool $show_revoked
 * @var bool $showCustomer
 */

use App\Model\Entity\CustomerProposal;

// Diving into one round is what turns the page into somewhere pages may be reordered and let go
// of: everything else here is about reading what is there.
$inside = $round !== null;

// Only where something is held underneath is there anything to leave out.
$holdsContracts = in_array($scope[0], ['customer', 'customerProposal'], true);

/**
 * What the rest of the address is carrying, so that one switch does not turn the others off.
 *
 * @param array<string> $fields Which of them this form has to take along.
 * @return string
 */
$carrying = function (array $fields): string {
    $carried = '';

    foreach ($fields as $field) {
        $said = $this->getRequest()->getQuery($field);

        if ($said !== null) {
            $carried .= $this->Form->hidden($field, ['value' => $said]);
        }
    }

    return $carried;
};
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Customer'),
                ['controller' => 'Customers', 'action' => 'view', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Edit Customer'),
                ['controller' => 'Customers', 'action' => 'edit', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php if ($contract !== null) : ?>
                <?= $this->AuthLink->link(
                    __('View Contract'),
                    ['controller' => 'Contracts', 'action' => 'view', $contract->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?= $this->AuthLink->link(
                __d('app_files', 'Documentations'),
                ['controller' => 'Documentations', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="documents content">
            <?php
            // Who this is and where it stands is the whereabouts underneath, so the heading says
            // it once rather than twice.
            ?>
            <?= $contract === null
                ? $this->element('Customers/heading', ['doing' => __('Documents'), 'withName' => false])
                : $this->element('Contracts/heading', ['doing' => __('Documents'), 'withName' => false]) ?>
            <?= $this->element('Documents/whereabouts') ?>
        </div>
        <br>
        <div class="documents content">
            <?php
            // Papers of a contract are drawn up in one place and one place only: the form that
            // draws them up. Opened from inside a round, it starts out in that round.
            $inARound = $round instanceof CustomerProposal;
            ?>
            <?= $this->AuthLink->link(
                __('New Customer Proposal'),
                [
                    'controller' => 'CustomerProposals',
                    'action' => 'add',
                    'contract_id' => null,
                ],
                ['class' => 'button button-small float-right win-link'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Contract Proposal'),
                [
                    'controller' => 'ContractProposals',
                    'action' => 'add',
                    // Opened from inside a proposal the papers start out in it. Opened from
                    // anywhere else the form asks which proposal they belong to, or draws one up.
                    '?' => $inARound ? ['proposal_id' => $round->id] : [],
                ],
                ['class' => 'button button-small float-right win-link'],
            ) ?>
            <h4><?= __('Proposals') ?></h4>
            <p><?= __('Every proposal in view. A proposal is put to the customer, and what it does'
                . ' for each of their contracts is a part of it - so the row names those contracts'
                . ' and what is asked of each, and the papers of all of them are below.') ?></p>
            <?= $this->element('Documents/rounds', ['working' => true]) ?>
            <?php
            // Under the table rather than over it: the buttons above have the corner, and what
            // this switch does is only worth asking once somebody has read what is there.
            ?>
            <div class="clearfix">
                <div class="float-right">
                    <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
                    <?= $carrying(['agenda', 'proposal_id', 'with_contracts']) ?>
                    <?= $this->Form->control('show_revoked', [
                        'label' => __('Show revoked proposals'),
                        'type' => 'checkbox',
                        'checked' => $show_revoked,
                        'onchange' => $this::SUBMIT_ON_CHANGE,
                    ]) ?>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
        <br>
        <div class="documents content">
            <?php if ($holdsContracts) : ?>
            <div class="float-right">
                <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
                <?= $carrying(['agenda', 'proposal_id', 'show_revoked']) ?>
                <?= $this->Form->control('with_contracts', [
                    'label' => __('Papers of the Contracts As Well'),
                    'type' => 'checkbox',
                    'checked' => $with_contracts,
                    'onchange' => $this::SUBMIT_ON_CHANGE,
                ]) ?>
                <?= $this->Form->end() ?>
            </div>
            <?php endif; ?>
            <h4><?= h(__('Documents - {0}', $about)) ?></h4>
            <div class="related">
                <?php if ($inside) : ?>
                    <?php
                    // The row that says nothing has come back offers the filing too, but it is
                    // gone once the first scan is there - and the rest of them arrive later.
                    ?>
                    <?= $this->AuthLink->link(
                        __('Add Files'),
                        [
                            'action' => 'addPages',
                            '?' => [
                                'proposal_id' => $round->id,
                                'agenda' => $round instanceof CustomerProposal
                                    ? 'CustomerProposals'
                                    : 'ContractProposals',
                            ],
                        ],
                        ['class' => 'button button-small float-right'],
                    ) ?>
                <?php endif; ?>
                <h5><?= __('Received Documents') ?></h5>
                <p><?= __('The papers that came back. They are filed against the round they answer,'
                    . ' so the row says which one that is.') ?></p>
                <?php $this->Preview->load() ?>
                <?= $this->cell('Documents', $scope, [
                    'generatedByUs' => false,
                    'withWhatIsMissing' => true,
                    'withContracts' => $with_contracts,
                    'withRevoked' => $show_revoked,
                    'manage' => $inside,
                    'thumbnails' => $inside,
                ]) ?>
            </div>
            <div class="related">
                <h5><?= __('Generated Documents') ?></h5>
                <p><?= __('What we generated, and what is still to be. A document is generated once'
                    . ' and handed back afterwards, so these are the very files the customer was'
                    . ' given - and a row that has none yet is a paper waiting to be drawn.') ?></p>
                <?= $this->cell('Documents', $scope, [
                    'generatedByUs' => true,
                    'withWhatIsMissing' => true,
                    'withContracts' => $with_contracts,
                    'withRevoked' => $show_revoked,
                    'manage' => $inside,
                    'thumbnails' => $inside,
                ]) ?>
            </div>
        </div>
    </div>
</div>
