<?php
/**
 * A customer's own facts, as the pages about them open.
 *
 * Printing and the papers on file both start by saying who is being looked at, and they have to
 * say it the same way - what is here is what a customer is, not what either page then does with
 * it.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 */
?>
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
                        . ' (' . ($customer->verifyIdentityNumber() ? __('OK') : __('Invalid')) . ')'
                ) : '' ?></td>
            </tr>
            <tr>
                <th><?= __('VAT Number') ?></th>
                <td><?= h($customer->vat_number) ?></td>
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
                <th><?= __('Synchronize to Accounting System') ?></th>
                <td><?= $customer->sync_to_accounting ? __('Yes') : __('No'); ?></td>
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
