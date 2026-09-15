<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var bool $show_historical_records
 */

// The RADIUS accounts below are drawn by a cell, and a cell renders in a view of its own, so
// a block it asked for would never reach this page's layout - the script is asked for here.
$this->Html->script('lazy-load.js', ['block' => true]);

// The findings banner is drawn by the ajax layout, which carries no css block, so its style is
// asked for here - on the page that will be holding it.
$this->Html->css('problems', ['block' => true]);
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Contract'),
                ['action' => 'edit', $contract->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Contract'),
                ['action' => 'delete', $contract->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Contract'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
            <br>
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'controller' => 'Documents',
                    'action' => 'manage',
                    'customer_id' => $contract->customer_id,
                    'contract_id' => $contract->id,
                ],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('app_files', 'Documentations'),
                ['controller' => 'Documentations', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('List Customer Messages'),
                ['controller' => 'CustomerMessages', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Access Credentials'),
                ['controller' => 'AccessCredentials', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Service Overrides'),
                ['controller' => 'ServiceOverrides', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Historical Connections'),
                ['controller' => 'HistoricalConnections', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
        <br>
        <div class="side-labels">
            <h4 class="heading"><?= __('Labels') ?></h4>
            <?php foreach ($contract->customer->customer_labels as $customer_label) : ?>
                <?= $this->Html->link(
                    $customer_label->label->name ?? '(' . $customer_label->label->id . ')',
                    ['controller' => 'CustomerLabels', 'action' => 'view', $customer_label->id],
                    [
                        'class' => 'app-label win-link',
                        'title' => h($customer_label->label->caption) . PHP_EOL
                            . h($customer_label->created) . PHP_EOL
                            . h($customer_label->note),
                        'style' => $customer_label->label->style,
                    ],
                ) ?>
            <?php endforeach ?>
        </div>
        <div class="side-nav">
            <?= $this->AuthLink->link(
                __('New Customer Label'),
                ['controller' => 'CustomerLabels', 'action' => 'add'],
                ['class' => 'side-nav-item win-link'],
            ) ?>
        </div>
        <?php if (!($this->getRequest()->getQuery('win-link') == 'true')) : ?>
        <div class="side-nav" style="position: fixed; bottom: 1rem;">
            <h4 class="heading"><?= __('Sections') ?></h4>
            <?= $this->AuthLink->link(
                __('Contract'),
                ['action' => 'view', $contract->id, '#' => 'contract'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Contract Versions'),
                ['action' => 'view', $contract->id, '#' => 'contract-versions'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Billings'),
                ['action' => 'view', $contract->id, '#' => 'billings'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Equipments'),
                ['action' => 'view', $contract->id, '#' => 'borrowed-equipments'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('IP Addresses'),
                ['action' => 'view', $contract->id, '#' => 'ip_addresses'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('RADIUS Accounts'),
                ['action' => 'view', $contract->id, '#' => 'radius-accounts'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Invoices'),
                ['action' => 'view', $contract->id, '#' => 'invoices'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Tasks'),
                ['action' => 'view', $contract->id, '#' => 'tasks'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
        <?php endif; ?>
    </aside>
    <div class="column column-90">
        <?php // the checks come to about as much work as the rest of the page, and none of ?>
        <?php // it is what the page was opened to read - so it is asked for afterwards ?>
        <div
            class="lazy-load"
            data-url="<?= $this->Url->build([
                'action' => 'problems',
                $contract->id,
                'customer_id' => $contract->customer_id,
            ]) ?>"
            data-error="<?= h(__('What does not add up on this contract could not be loaded.')) ?>"
            data-trigger="load"
        ></div>
        <div class="contracts view content">
            <?= $this->AuthLink->link(
                __d('app_files', 'Documentations'),
                ['controller' => 'Documentations', 'action' => 'index'],
                ['class' => 'button float-right'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'controller' => 'Documents',
                    'action' => 'manage',
                    'customer_id' => $contract->customer_id,
                    'contract_id' => $contract->id,
                ],
                ['class' => 'button float-right'],
            ) ?>
            <a id="contract"></a>
            <?= $this->element('Contracts/heading') ?>
            <?= $this->element('Contracts/facts', ['showMap' => true]) ?>
            <div class="row">
                <div class="column">
                    <div class="text">
                        <strong><?= __('Access Description') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($contract->access_description)); ?>
                        </blockquote>
                    </div>
                </div>
                <div class="column">
                    <div class="text">
                        <strong><?= __('Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($contract->note)); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
            <?php
            // What the papers say beyond the standard terms, in the order they are printed in.
            // The service type's own terms are shown here as well, because from the customer's
            // side they are part of this contract and the card should read like the paper.
            //
            // The row goes or stays whole. Most contracts have neither, and an empty pair of
            // quotations on every one of them would say nothing twice over.
            $service_terms = $contract->service_type->service_terms ?? null;
            ?>
            <?php if (!empty($service_terms) || !empty($contract->individual_terms)) : ?>
            <div class="row">
                <div class="column">
                    <div class="text">
                        <strong><?= __('Terms of the Services') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($service_terms)); ?>
                        </blockquote>
                    </div>
                </div>
                <div class="column">
                    <div class="text">
                        <strong><?= __('Individual Terms') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($contract->individual_terms)); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($contract->service_type !== null && $contract->service_type->have_contract_versions) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract Version'),
                    ['controller' => 'ContractVersions', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="contract-versions"><?= __('Contract Versions') ?></h4>
                <?= $this->element('Contracts/ContractVersions', [
                    'contract_versions' => $contract->contract_versions,
                    'historical_checkbox' => true,
                    'show_historical_records' => $show_historical_records,
                ]) ?>
            </div>
            <div style="clear: both;"></div>
            <?php endif; ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Billing'),
                    ['controller' => 'Billings', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <?php if ($contract->termination_date !== null) : ?>
                    <?= $this->AuthLink->postLink(
                        __('Terminate Related Billings'),
                        ['action' => 'terminateRelatedBillings', $contract->id],
                        [
                            'confirm' => __(
                                'Are you sure you want to terminate related billings for contract # {0}?',
                                $contract->number,
                            ),
                            'class' => 'button button-small float-right',
                        ],
                    ) ?>
                <?php endif ?>
                <h4 id="billings"><?= __('Billings') ?></h4>
                <?= $this->element('Contracts/Billings', [
                    'billings' => $contract->billings,
                    'historical_checkbox' => true,
                    'show_historical_records' => $show_historical_records,
                ]) ?>
                <?= $this->cell(
                    'ServiceOverridesStatus',
                    [
                        [$contract->id],
                    ],
                    [
                        'showContractNumber' => false,
                        'onlyActiveOverrides' => false,
                    ],
                ) ?>
            </div>
            <?php if ($contract->service_type !== null && $contract->service_type->have_equipments) : ?>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Borrowed Equipment'),
                            ['controller' => 'BorrowedEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <?php
                        if (
                            $contract->installation_date !== null
                            || $contract->uninstallation_date !== null
                        ) : ?>
                            <?= $this->AuthLink->postLink(
                                __('Set Dates Automatically'),
                                ['action' => 'setDatesForRelatedBorrowedEquipments', $contract->id],
                                [
                                    'confirm' => __(
                                        'Are you sure you want to set dates'
                                        . ' for related borrowed equipments for contract # {0}?',
                                        $contract->number,
                                    ),
                                    'class' => 'button button-small float-right',
                                ],
                            ) ?>
                        <?php endif ?>
                        <h4 id="borrowed-equipments"><?= __('Borrowed Equipments') ?></h4>
                        <?= $this->element('Contracts/BorrowedEquipments', [
                            'borrowed_equipments' => $contract->borrowed_equipments,
                            'historical_checkbox' => true,
                            'show_historical_records' => $show_historical_records,
                        ]) ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Sold Equipment'),
                            ['controller' => 'SoldEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4><?= __('Sold Equipments') ?></h4>
                        <?= $this->element('Contracts/SoldEquipments', [
                            'sold_equipments' => $contract->sold_equipments,
                        ]) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($contract->service_type !== null && $contract->service_type->have_ip_addresses) : ?>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Address'),
                            ['controller' => 'IpAddresses', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <?php // the recorded point, not the one looked up: a button that quietly
                        // disappears whenever the network management system is down reads as a
                        // decision rather than as an outage, and the form behind it says so itself ?>
                        <?= $contract->access_point_id !== null ? $this->AuthLink->link(
                            __('New IP Address From Range'),
                            ['controller' => 'IpAddresses', 'action' => 'addFromRange'],
                            ['class' => 'button button-small float-right win-link'],
                        ) : '' ?>
                        <h4 id="ip_addresses"><?= __('IP Addresses') ?></h4>
                        <?= $this->element('Contracts/IpAddresses', [
                            'ip_addresses' => $contract->ip_addresses,
                        ]) ?>
                        <div class="float-right">
                            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => []]) ?>
                            <?= $this->Form->control('show_historical_records', [
                                'label' => __('Show historical records'),
                                'type' => 'checkbox',
                                'checked' => $show_historical_records,
                                'onchange' => $this::SUBMIT_ON_CHANGE,
                            ]) ?>
                            <?= $this->Form->end() ?>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Network'),
                            ['controller' => 'IpNetworks', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4><?= __('IP Networks') ?></h4>
                        <?= $this->element('Contracts/IpNetworks', [
                            'ip_networks' => $contract->ip_networks,
                        ]) ?>
                        <div class="float-right">
                            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => []]) ?>
                            <?= $this->Form->control('show_historical_records', [
                                'label' => __('Show historical records'),
                                'type' => 'checkbox',
                                'checked' => $show_historical_records,
                                'onchange' => $this::SUBMIT_ON_CHANGE,
                            ]) ?>
                            <?= $this->Form->end() ?>
                        </div>
                    </div>
                </div>
            </div>
                <?php if ($show_historical_records) : ?>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Removed IP Address'),
                            ['controller' => 'RemovedIpAddresses', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4><?= __('Removed IP Addresses') ?></h4>
                        <?= $this->element('Contracts/RemovedIpAddresses', [
                            'removed_ip_addresses' => $contract->removed_ip_addresses,
                        ]) ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Removed IP Network'),
                            ['controller' => 'RemovedIpNetworks', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4><?= __('Removed IP Networks') ?></h4>
                        <?= $this->element('Contracts/RemovedIpNetworks', [
                            'removed_ip_networks' => $contract->removed_ip_networks,
                        ]) ?>
                    </div>
                </div>
            </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($contract->service_type !== null && $contract->service_type->have_radius_accounts) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New RADIUS Account'),
                    ['plugin' => 'Radius', 'controller' => 'Accounts', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="radius-accounts"><?= __('RADIUS Accounts') ?></h4>
                <?= $this->cell(
                    'Radius.Accounts',
                    [['Accounts.contract_id' => $contract->id]],
                    ['show_contracts' => false],
                ) ?>
            </div>
            <?php endif; ?>
        </div>
        <br>
        <div class="contracts view content">
            <div>
                <?= $this->AuthLink->postLink(
                    __('Unblock Debtor'),
                    [
                        'plugin' => 'Bookkeeping',
                        'controller' => 'Debtors',
                        'action' => 'unblock',
                        $contract->customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to unblock # {0}?', $contract->customer->id),
                    ],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Block Debtor'),
                    [
                        'plugin' => 'Bookkeeping',
                        'controller' => 'Debtors',
                        'action' => 'block',
                        $contract->customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to block # {0}?', $contract->customer->id),
                    ],
                ) ?>
                <h4 id="invoices"><?= __('Invoices') ?></h4>
                <?= $this->cell(
                    'Bookkeeping.Invoices',
                    [['Invoices.customer_id' => $contract->customer->id]],
                    ['show_customers' => false],
                ) ?>
            </div>
        </div>
        <br>
        <div class="contracts view content">
            <div>
                <?= $this->AuthLink->link(
                    __('New Task'),
                    ['controller' => 'Tasks', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="tasks"><?= __('Tasks') ?></h4>
                <?= $this->element('Contracts/Tasks', [
                    'tasks' => $contract->tasks,
                ]) ?>
            </div>
            <div class="related">
                <h4><?= __('Other Customer Tasks') ?></h4>
                <?= $this->element('Contracts/Tasks', [
                    'tasks' => $contract->customer->tasks,
                    'contract_column' => true,
                ]) ?>
            </div>
        </div>
    </div>
</div>
