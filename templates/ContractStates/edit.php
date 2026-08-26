<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractState $contractState
 * @var array<string, string> $roles
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $requiresOpenTaskTypes
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $contractState->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $contractState->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contract States'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractStates form content">
            <?= $this->Form->create($contractState) ?>
            <fieldset>
                <legend><?= __('Edit Contract State') ?></legend>

                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('name');
                        echo $this->Form->control('color', ['type' => 'color']);
                        echo $this->Form->control('usable_for_new_contract', [
                            'label' => __('Usable for New Contracts'),
                        ]);
                        echo $this->Form->control('active_services');
                        echo $this->Form->control('billed');
                        echo $this->Form->control('blocked');
                        echo $this->Form->control('show_on_dashboard', [
                            'label' => __('Show on Dashboard'),
                        ]);
                        echo $this->Form->control('dashboard_roles', [
                            'type' => 'select',
                            'multiple' => true,
                            'options' => $roles,
                            'label' => __('Dashboard Roles'),
                            'title' => __('Leave empty to show the state to everybody.'),
                        ]);
                        ?>
                    </div>

                    <div class="column">
                        <?php
                        echo $this->Form->control('requires_open_task_type_id', [
                            'empty' => true,
                        ]);
                        echo $this->Form->control('requires_no_open_tasks');
                        echo $this->Form->control('requires_no_active_billings');
                        echo $this->Form->control('requires_no_future_billings');
                        echo $this->Form->control('requires_no_borrowed_equipments');
                        ?>
                    </div>
                </div>

                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('note');
                        ?>
                    </div>
                </div>

                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('requires_installation_date');
                        echo $this->Form->control('requires_uninstallation_date');
                        echo $this->Form->control('requires_termination_date');
                        echo $this->Form->control('requires_versions_matching_termination', [
                            'label' => __('Requires Contract Versions Matching the Termination Date'),
                        ]);
                        echo $this->Form->control('requires_billings_matching_termination', [
                            'label' => __('Requires Billings Matching the Termination Date'),
                        ]);
                        echo $this->Form->control('requires_equipments_matching_uninstallation', [
                            'label' => __('Requires Borrowed Equipments Matching the Uninstallation Date'),
                        ]);
                        ?>
                        <br>
                        <?php
                        echo $this->Form->control('requires_no_assigned_ip_addresses_or_networks', [
                            'label' => __('Requires No Assigned IP Addresses or Networks'),
                        ]);
                        echo $this->Form->control('requires_no_active_radius_accounts', [
                            'label' => __('Requires No Active RADIUS Accounts'),
                        ]);
                        ?>
                    </div>

                    <div class="column">
                        <?php
                        echo $this->Form->control('requires_contract_version');
                        echo $this->Form->control('requires_active_contract_version');
                        echo $this->Form->control('requires_active_or_future_contract_version', [
                            'label' => __('Requires Active or Future Contract Version'),
                        ]);
                        echo $this->Form->control('requires_no_active_or_future_contract_versions', [
                            'label' => __('Requires No Active or Future Contract Versions'),
                        ]);
                        echo $this->Form->control('requires_no_active_obligations');
                        ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
