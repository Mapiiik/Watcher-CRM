<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Form\Form $printForm
 * @var \App\Model\Enum\ContractPrintType|null $printType
 * @var \App\Model\Entity\Contract $contract
 * @var iterable<\App\Model\Entity\ContractVersionProposal> $proposals
 * @var \App\Model\Entity\ContractVersionProposal|null $proposal
 * @var array<string, string> $documentTypes
 */

$howAProposalReads = function ($one): string {
    $version = $one->contract_version ?? null;
    $period = $version === null
        ? ''
        : $version->valid_from . ' - ' . ($version->valid_until ?: __('indefinitely'));

    return sprintf(
        '%s - %s - %s (%s)',
        $one->effective_from,
        $one->purpose->label(),
        $period,
        $one->getState(),
    );
};

$options = [];
foreach ($proposals as $one) {
    $options[$one->id] = $howAProposalReads($one);
}

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Contract'),
                ['action' => 'view', $contract->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Edit Contract'),
                ['action' => 'edit', $contract->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contracts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= __('Contract No.') ?><h3><?= h($contract->number) ?></h3>
            <h5><?=
                ($contract->service_type !== null ? $contract->service_type->name : '') .
                ($contract->installation_address !== null ? ' - ' . $contract->installation_address->address : '')
            ?></h5>
            <div class="row">
                <div class="column">
                    <table style="<?= $contract->style ?>">
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $contract->customer !== null ? $this->Html->link(
                                $contract->customer->name ?? '(' . $contract->customer->id . ')',
                                ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $contract->customer !== null ? h($contract->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract State') ?></th>
                            <td><?= $contract->contract_state !== null ? $this->Html->link(
                                $contract->contract_state->name ?? '(' . $contract->contract_state->id . ')',
                                ['controller' => 'ContractStates', 'action' => 'view', $contract->contract_state->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <td><?= $contract->service_type !== null ? $this->Html->link(
                                $contract->service_type->name ?? '(' . $contract->service_type->id . ')',
                                ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <td><?= h($contract->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Subscriber Verification Code') ?></th>
                            <td><?= h($contract->subscriber_verification_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Address') ?></th>
                            <td><?= $contract->installation_address !== null ? $this->Html->link(
                                $contract->installation_address->full_address,
                                ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id],
                            ) . ($contract->installation_address->note ?
                                ' (' . h($contract->installation_address->note) . ')' : ''
                            ) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Access Point') ?></th>
                            <td><?= $this->element('AccessPoints/link', [
                                'id' => $contract->access_point_id,
                                'name' => $contract->access_point->data?->name,
                                'answer' => $contract->access_point,
                            ]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Commission') ?></th>
                            <td><?= $contract->commission !== null ? $this->Html->link(
                                $contract->commission->name ?? '(' . $contract->commission->id . ')',
                                ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Vip') ?></th>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee') ?></th>
                            <td><?= h($contract->activation_fee) ?><?= $contract->service_type !== null ?
                                ' (' . h($contract->service_type->activation_fee) . ')' : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee With Obligation') ?></th>
                            <td><?=
                                h($contract->activation_fee_with_obligation)
                            ?><?=
                                $contract->service_type !== null ?
                                    ' (' . h($contract->service_type->activation_fee_with_obligation) . ')' : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Installation/Establishment Date') ?></th>
                            <td><?= h($contract->installation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Technician') ?></th>
                            <td><?= $contract->installation_technician !== null ? $this->Html->link(
                                $contract->installation_technician->name
                                ?? '(' . $contract->installation_technician->id . ')',
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->installation_technician->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Uninstallation/Cancellation Date') ?></th>
                            <td><?= h($contract->uninstallation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Uninstallation Technician') ?></th>
                            <td><?= $contract->uninstallation_technician !== null ? $this->Html->link(
                                $contract->uninstallation_technician->name
                                ?? '(' . $contract->uninstallation_technician->id . ')',
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->uninstallation_technician->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Date of Termination of Services') ?></th>
                            <td><?= h($contract->termination_date) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $contract]) ?>
                </div>
            </div>
            <?php if ($contract->service_type !== null && $contract->service_type->have_contract_versions) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract Version'),
                    ['controller' => 'ContractVersions', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Contract Versions') ?></h4>
                <?= $this->element('Contracts/ContractVersions', [
                    'contract_versions' => $contract->contract_versions,
                ]) ?>
            </div>
            <br>
            <?php endif; ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Proposal'),
                    [
                        'controller' => 'ContractVersionProposals',
                        'action' => 'add',
                        'customer_id' => $contract->customer_id,
                        'contract_id' => $contract->id,
                    ],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Proposals') ?></h4>
                <?= $this->element('Contracts/ContractVersionProposals', [
                    'contract_version_proposals' => $proposals,
                ]) ?>
            </div>
            <br>
            <?= $this->Form->create($printForm, [
                'type' => 'get',
                'valueSources' => ['query'],
                'url' => [
                    'action' => 'print',
                    $contract->id,
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Print Documents') ?></legend>
                <p><?= __('A document is printed from a proposal, so that the same paper printed'
                    . ' twice is the same paper. What it says is what the proposal took down, not'
                    . ' what the records happen to say today.') ?></p>
                <?php
                if ($options === []) {
                    echo '<p>' . __('There is no proposal on this contract yet.') . '</p>';
                } else {
                    echo $this->Form->control('proposal_id', [
                        'label' => __('Proposal'),
                        'options' => $options,
                        'empty' => true,
                        'value' => $proposal?->id,
                        'required' => true,
                        'onchange' => $this::SUBMIT_ON_CHANGE,
                    ]);

                    if ($proposal !== null) {
                        echo $this->Form->control('document_type', [
                            'label' => __('Document Type'),
                            'options' => $documentTypes,
                            'empty' => true,
                            'value' => $printType?->value,
                            'required' => true,
                            'onchange' => $this::SUBMIT_ON_CHANGE,
                        ]);
                        echo $this->Form->control('signed', [
                            'label' => __('Signed'),
                            'type' => 'checkbox',
                        ]);
                    }
                }
                ?>
            </fieldset>
            <?php if ($proposal !== null && $printType !== null) : ?>
                <?= $this->Form->button(__('Print to PDF'), [
                    'name' => 'submit_action',
                    'value' => 'pdf',
                ]) ?>
            <?php endif; ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
