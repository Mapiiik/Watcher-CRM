<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var bool $show_historical_records
 */

use App\BusinessRegister\IdentityNumberStatus;
use App\BusinessRegister\VatNumberStatus;

// The RADIUS accounts below are drawn by a cell, and a cell renders in a view of its own, so
// a block it asked for would never reach this page's layout - the script is asked for here.
$this->Html->script('lazy-load.js', ['block' => true]);

// each reaches a register, so ask once and read the answer twice
$identityNumberCheck = $customer->identityNumberCheck();
$vatNumberCheck = $customer->vatNumberCheck();

/**
 * A remark in brackets after a number, marked as an error where it is one.
 *
 * @param string $note What to say.
 * @param bool $wrong Whether it is something wrong rather than something to know.
 * @return string
 */
$remark = function (string $note, bool $wrong = false): string {
    $note = ' (' . h($note) . ')';

    return $wrong ? '<span class="error-text">' . $note . '</span>' : $note;
};
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Customer'),
                ['action' => 'edit', $customer->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Customer'),
                ['action' => 'delete', $customer->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Customer'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
            <br>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $customer->id],
                ['class' => 'side-nav-item', 'target' => 'print'],
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
            <?php foreach ($customer->customer_labels as $customer_label) : ?>
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
                __('Customer'),
                ['action' => 'view', $customer->id, '#' => 'customer'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Logins'),
                ['action' => 'view', $customer->id, '#' => 'logins'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Addresses'),
                ['action' => 'view', $customer->id, '#' => 'addresses'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Contracts'),
                ['action' => 'view', $customer->id, '#' => 'contracts'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Billings'),
                ['action' => 'view', $customer->id, '#' => 'billings'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Equipments'),
                ['action' => 'view', $customer->id, '#' => 'borrowed-equipments'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('IP Addresses'),
                ['action' => 'view', $customer->id, '#' => 'ip_addresses'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('RADIUS Accounts'),
                ['action' => 'view', $customer->id, '#' => 'radius-accounts'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Invoices'),
                ['action' => 'view', $customer->id, '#' => 'invoices'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Tasks'),
                ['action' => 'view', $customer->id, '#' => 'tasks'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
        <?php endif; ?>
    </aside>
    <div class="column column-90">
        <div class="customers view content">
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $customer->id],
                ['class' => 'button float-right', 'target' => 'print'],
            ) ?>
            <a id="customer"></a>
            <?= __('Customer No.') ?><h3><?= h($customer->number) ?></h3>
            <h5><?= h($customer->name) ?></h5>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Company') ?></th>
                            <td><?= h($customer->company) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Title') ?></th>
                            <td><?= h($customer->title) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('First Name') ?></th>
                            <td><?= h($customer->first_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Name') ?></th>
                            <td><?= h($customer->last_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Suffix') ?></th>
                            <td><?= h($customer->suffix) ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Date Of Birth') ?></th>
                            <td><?= h($customer->date_of_birth) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Identity Card Number') ?></th>
                            <td><?= h($customer->identity_card_number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Identity Number') ?></th>
                            <td><?= $customer->identity_number !== null ? (
                                h($customer->identity_number)
                                    . ($customer->verifyIdentityNumber()
                                        ? $remark(__('OK'))
                                        : $remark(__('Invalid'), wrong: true))
                                    . ($identityNumberCheck !== null ? $remark(
                                        $identityNumberCheck->note(),
                                        wrong: $identityNumberCheck->status === IdentityNumberStatus::NotFound
                                            || !$customer->isKnownAs($identityNumberCheck->company),
                                    ) : '')
                            ) : '' ?>
                                <?= $customer->identityNumberPortalUrl() !== null ? $this->Html->link(
                                    __('Register'),
                                    $customer->identityNumberPortalUrl(),
                                    ['target' => '_blank', 'rel' => 'noopener'],
                                ) : '' ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= __('VAT Number') ?></th>
                            <td><?= h($customer->vat_number) ?><?= $vatNumberCheck !== null
                                ? $remark(
                                    $vatNumberCheck->status->label(),
                                    wrong: $vatNumberCheck->status === VatNumberStatus::Invalid,
                                )
                                    . ($vatNumberCheck->company !== null
                                        ? $remark(
                                            $vatNumberCheck->company,
                                            wrong: !$customer->isKnownAs($vatNumberCheck->company),
                                        )
                                        : '')
                                : '' ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Www') ?></th>
                            <td><?= h($customer->www) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Account') ?></th>
                            <td><?= h($customer->bank_account) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Code') ?></th>
                            <td><?= h($customer->bank_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Name') ?></th>
                            <td><?= h($customer->bank_name) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Accounting Profile') ?></th>
                            <td><?= $customer->accounting_profile !== null ? $this->Html->link(
                                $customer->accounting_profile->name ?? '(' . $customer->accounting_profile->id . ')',
                                [
                                    'controller' => 'AccountingProfiles',
                                    'action' => 'view',
                                    $customer->accounting_profile->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Dealer') ?></th>
                            <td><?= h($customer->dealer->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice Delivery Type') ?></th>
                            <td><?= h($customer->invoice_delivery_type->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Individual Maturity Period') ?></th>
                            <td><?= $customer->individual_maturity_period ? __n(
                                '{0} day',
                                '{0} days',
                                $customer->individual_maturity_period,
                                $customer->individual_maturity_period,
                            ) : ''; ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Agrees to Processing of Personal Data') ?></th>
                            <td><?= $customer->agree_gdpr ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agrees to Receive All Correspondence Related to Billing') ?></th>
                            <td><?= $customer->agree_mailing_billing ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agrees to Receive Information About Outages And Malfunctions') ?></th>
                            <td><?= $customer->agree_mailing_outages ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agrees to Receive Commercial Communications') ?></th>
                            <td><?= $customer->agree_mailing_commercial ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                    <?= $this->element('common/audit', ['entity' => $customer]) ?>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <div class="text">
                        <strong><?= __('Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($customer->note)); ?>
                        </blockquote>
                    </div>
                </div>
                <div class="column">
                    <div class="text">
                        <strong><?= __('Internal Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($customer->internal_note)); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Email'),
                            ['controller' => 'Emails', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4><?= __('Emails') ?></h4>
                        <?php if (!empty($customer->emails)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Email') ?></th>
                                    <th><?= __('Use For Billing') ?></th>
                                    <th><?= __('Use For Outages') ?></th>
                                    <th><?= __('Use For Commercial') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->emails as $email) : ?>
                                <tr>
                                    <td><?= $this->Html->link(h($email->email), 'mailto:' . $email->email) ?></td>
                                    <td><?= $email->use_for_billing ? __('Yes') : __('No'); ?></td>
                                    <td><?= $email->use_for_outages ? __('Yes') : __('No'); ?></td>
                                    <td><?= $email->use_for_commercial ? __('Yes') : __('No'); ?></td>
                                    <td><?= h($email->note) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'Emails', 'action' => 'view', $email->id],
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'Emails', 'action' => 'edit', $email->id],
                                            ['class' => 'win-link'],
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            ['controller' => 'Emails', 'action' => 'delete', $email->id],
                                            ['confirm' => __('Are you sure you want to delete # {0}?', $email->id)],
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Phone'),
                            ['controller' => 'Phones', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4><?= __('Phones') ?></h4>
                        <?php if (!empty($customer->phones)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Phone') ?></th>
                                    <th><?= __('Use For Billing') ?></th>
                                    <th><?= __('Use For Outages') ?></th>
                                    <th><?= __('Use For Commercial') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->phones as $phone) : ?>
                                <tr>
                                    <td><?= h($phone->phone) ?></td>
                                    <td><?= $phone->use_for_billing ? __('Yes') : __('No'); ?></td>
                                    <td><?= $phone->use_for_outages ? __('Yes') : __('No'); ?></td>
                                    <td><?= $phone->use_for_commercial ? __('Yes') : __('No'); ?></td>
                                    <td><?= h($phone->note) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'Phones', 'action' => 'view', $phone->id],
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'Phones', 'action' => 'edit', $phone->id],
                                            ['class' => 'win-link'],
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            ['controller' => 'Phones', 'action' => 'delete', $phone->id],
                                            ['confirm' => __('Are you sure you want to delete # {0}?', $phone->id)],
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Login'),
                    ['controller' => 'Logins', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="logins"><?= __('Logins') ?></h4>
                <?php if (!empty($customer->logins)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Login') ?></th>
                            <th><?= __('Rights') ?></th>
                            <th><?= __('Locked') ?></th>
                            <th><?= __('Last Granted') ?></th>
                            <th><?= __('Last Granted IP Address') ?></th>
                            <th><?= __('Last Denied') ?></th>
                            <th><?= __('Last Denied IP Address') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->logins as $login) : ?>
                        <tr>
                            <td><?= h($login->login) ?></td>
                            <td><?= h($login->rights->label()) ?></td>
                            <td><?= $login->locked ? __('Yes') : __('No'); ?></td>
                            <td><?= h($login->last_granted) ?></td>
                            <td><?= h($login->last_granted_ip) ?></td>
                            <td><?= h($login->last_denied) ?></td>
                            <td><?= h($login->last_denied_ip) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Logins', 'action' => 'view', $login->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Logins', 'action' => 'edit', $login->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Logins', 'action' => 'delete', $login->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $login->id)],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Address'),
                    ['controller' => 'Addresses', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="addresses"><?= __('Addresses') ?></h4>
                <?php if (!empty($customer->addresses)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Company') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('First Name') ?></th>
                            <th><?= __('Last Name') ?></th>
                            <th><?= __('Suffix') ?></th>
                            <th><?= __('Street') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Entrance') ?></th>
                            <th><?= __('Unit') ?></th>
                            <th><?= __('City') ?></th>
                            <th><?= __('Zip') ?></th>
                            <th><?= __('Country') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Address Registry Reference') ?></th>
                            <th class="actions"><?= __('Map location') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->addresses as $address) : ?>
                        <tr>
                            <td><?= h($address->type->label()) ?></td>
                            <td><?= h($address->company) ?></td>
                            <td><?= h($address->title) ?></td>
                            <td><?= h($address->first_name) ?></td>
                            <td><?= h($address->last_name) ?></td>
                            <td><?= h($address->suffix) ?></td>
                            <td><?= h($address->street) ?></td>
                            <td><?= h($address->number) ?></td>
                            <td><?= h($address->entrance) ?></td>
                            <td><?= h($address->unit) ?></td>
                            <td><?= h($address->city) ?></td>
                            <td><?= h($address->zip) ?></td>
                            <td><?= $address->country !== null ? h($address->country->name) : '' ?></td>
                            <td><?= h($address->note) ?></td>
                            <td><?=
                                $address->address_registry_reference === null
                                || $address->address_registry_source === null ?
                                    '<span style="color: red;">' . __('unknown') . '</span>'
                                    :
                                    h($address->address_registry_reference)
                                        . ' (' . h(strtoupper($address->address_registry_source)) . ')'
                            ?></td>
                            <td class="actions">
                                <?= $address->gps_x !== null && $address->gps_y !== null ?
                                    '' : '<span style="color: red;">' . __('unknown') . '</span>' ?>
                                <?= $this->element('Maps.Maps/links', [
                                    'lat' => $address->gps_y,
                                    'lng' => $address->gps_x,
                                ]) ?>
                            </td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Addresses', 'action' => 'view', $address->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Addresses', 'action' => 'edit', $address->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Addresses', 'action' => 'delete', $address->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $address->id)],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract'),
                    ['controller' => 'Contracts', 'action' => 'add'],
                    ['class' => 'button button-small float-right'],
                ) ?>
                <h4 id="contracts"><?= __('Contracts') ?></h4>
                <?php if (!empty($customer->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Contract State') ?></th>
                            <th><?= __('Service Type') ?></th>
                            <th><?= __('Installation Address') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Access Point') ?></th>
                            <th><?= __('Installation/Establishment Date') ?></th>
                            <th><?= __('Uninstallation/Cancellation Date') ?></th>
                            <th><?= __('Date of Termination of Services') ?></th>
                            <th><?= __('Obligation Until') ?></th>
                            <th><?= __('Subscriber Verification Code') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td><?= h($contract->number) ?></td>
                            <td><?=
                                $contract->contract_state !== null ? h($contract->contract_state->name) : '' ?></td>
                            <td><?= $contract->service_type !== null ? h($contract->service_type->name) : '' ?></td>
                            <td><?= $contract->installation_address !== null ?
                                h($contract->installation_address->full_address) : '' ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $this->element('AccessPoints/link', [
                                'id' => $contract->access_point_id,
                                'name' => $contract->access_point_name,
                                ]) ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td><?= h($contract->uninstallation_date) ?></td>
                            <td><?= h($contract->termination_date) ?></td>
                            <?php $maxObligationUntil = $contract->getMaxObligationUntil(); ?>
                            <td style="<?=
                                isset($maxObligationUntil)
                                && $maxObligationUntil->isFuture() ?
                                    'color: red;' : ''
                            ?>"><?= h($maxObligationUntil) ?></td>
                            <td><?= h($contract->subscriber_verification_code) ?></td>
                            <td><?= h($contract->note) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Contracts', 'action' => 'view', $contract->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Contracts', 'action' => 'edit', $contract->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Contracts', 'action' => 'delete', $contract->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id)],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <hr />
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Billing'),
                    ['controller' => 'Billings', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="billings"><?= __('Related Billings') ?></h4>
                <?= $this->element('Contracts/Billings', [
                    'billings' => $customer->billings,
                    'contract_column' => true,
                    'historical_checkbox' => true,
                    'show_historical_records' => $show_historical_records,
                ]) ?>
                <?= $this->cell(
                    'ServiceOverridesStatus',
                    [
                        collection($customer->contracts)->extract('id')->toList(),
                    ],
                    [
                        'showContractNumber' => true,
                        'onlyActiveOverrides' => false,
                    ],
                ) ?>
            </div>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Borrowed Equipment'),
                            ['controller' => 'BorrowedEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4 id="borrowed-equipments"><?= __('Related Borrowed Equipments') ?></h4>
                        <?= $this->element('Contracts/BorrowedEquipments', [
                            'borrowed_equipments' => $customer->borrowed_equipments,
                            'contract_column' => true,
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
                        <h4><?= __('Related Sold Equipments') ?></h4>
                        <?= $this->element('Contracts/SoldEquipments', [
                            'sold_equipments' => $customer->sold_equipments,
                            'contract_column' => true,
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Address'),
                            ['controller' => 'IpAddresses', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('New IP Address From Range'),
                            ['controller' => 'IpAddresses', 'action' => 'addFromRange'],
                            ['class' => 'button button-small float-right win-link'],
                        ) ?>
                        <h4 id="ip_addresses"><?= __('Related IP Addresses') ?></h4>
                        <?= $this->element('Contracts/IpAddresses', [
                            'ip_addresses' => $customer->ip_addresses,
                            'contract_column' => true,
                        ]) ?>
                        <div class="float-right">
                            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => []]) ?>
                            <?= $this->Form->control('show_historical_records', [
                                'label' => __('Show historical records'),
                                'type' => 'checkbox',
                                'checked' => $show_historical_records,
                                'onchange' => 'this.form.submit();',
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
                        <h4><?= __('Related IP Networks') ?></h4>
                        <?= $this->element('Contracts/IpNetworks', [
                            'ip_networks' => $customer->ip_networks,
                            'contract_column' => true,
                        ]) ?>
                    </div>
                        <div class="float-right">
                            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => []]) ?>
                            <?= $this->Form->control('show_historical_records', [
                                'label' => __('Show historical records'),
                                'type' => 'checkbox',
                                'checked' => $show_historical_records,
                                'onchange' => 'this.form.submit();',
                            ]) ?>
                            <?= $this->Form->end() ?>
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
                        <h4><?= __('Related Removed IP Addresses') ?></h4>
                        <?= $this->element('Contracts/RemovedIpAddresses', [
                            'removed_ip_addresses' => $customer->removed_ip_addresses,
                            'contract_column' => true,
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
                        <h4><?= __('Related Removed IP Networks') ?></h4>
                        <?= $this->element('Contracts/RemovedIpNetworks', [
                            'removed_ip_networks' => $customer->removed_ip_networks,
                            'contract_column' => true,
                        ]) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New RADIUS Account'),
                    ['plugin' => 'Radius', 'controller' => 'Accounts', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="radius-accounts"><?= __('Related RADIUS Accounts') ?></h4>
                <?= $this->cell(
                    'Radius.Accounts',
                    [['Accounts.customer_id' => $customer->id]],
                ) ?>
            </div>
            <hr />
            <div class="related">
                <?= $this->AuthLink->postLink(
                    __('Unblock Debtor'),
                    [
                        'plugin' => 'Bookkeeping',
                        'controller' => 'Debtors',
                        'action' => 'unblock',
                        $customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to unblock # {0}?', $customer->id),
                    ],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Block Debtor'),
                    [
                        'plugin' => 'Bookkeeping',
                        'controller' => 'Debtors',
                        'action' => 'block',
                        $customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to block # {0}?', $customer->id),
                    ],
                ) ?>
                <h4 id="invoices"><?= __('Invoices') ?></h4>
                <?= $this->cell(
                    'Bookkeeping.Invoices',
                    [['Invoices.customer_id' => $customer->id]],
                    ['show_customers' => false],
                ) ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Task'),
                    ['controller' => 'Tasks', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4 id="tasks"><?= __('Tasks') ?></h4>
                <?= $this->element('Contracts/Tasks', [
                    'tasks' => $customer->tasks,
                    'contract_column' => true,
                ]) ?>
            </div>
        </div>
    </div>
</div>
