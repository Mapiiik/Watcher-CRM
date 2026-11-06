<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Audit\AuditTrail;
use App\Model\Entity\Billing;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\BillingsTable;
use App\Model\Table\CustomerMessagesTable;
use App\Utility\ServiceChangeMessageBuilder;
use Cake\ORM\Locator\LocatorAwareTrait;
use Settings\Utility\Settings;

/**
 * Moves a billing to another service: the old one ends the day before and a new one takes over.
 *
 * One billing and hundreds of them go through here alike, so a tariff changed in bulk comes out the
 * same as one changed by hand.
 */
final class BillingServiceChange
{
    use LocatorAwareTrait;

    /**
     * Ends the billing, starts its replacement and tells the customer if asked to.
     *
     * @param \App\Model\Entity\Billing $original The billing, with its contract, customer and service.
     * @param array<mixed> $changes What the new billing says differently.
     * @param array<string, bool> $saveOptions What the save may override.
     * @param bool $notifyCustomer Whether the customer is told about the change.
     * @param bool $withoutLegislativeInformation Whether the letter leaves that part out.
     * @return \App\Model\Entity\Billing The new billing.
     * @throws \App\Service\BillingChangeRefused When the records refuse it.
     */
    public function apply(
        Billing $original,
        array $changes,
        array $saveOptions,
        bool $notifyCustomer = false,
        bool $withoutLegislativeInformation = false,
    ): Billing {
        $billings = $this->fetchTable(BillingsTable::class);

        // Prepare new billing entity
        $originalData = $original->toArray();
        unset(
            $originalData['id'],
            $originalData['created'],
            $originalData['created_by'],
            $originalData['modified'],
            $originalData['modified_by'],
            $originalData['contract'],
            $originalData['customer'],
            $originalData['service'],
        );

        // The money is held on the entity as an object and the marshaller takes only what a form
        // would send, so the copy is refused for any field the form then does not overwrite.
        $originalData['price'] = $original->price?->toString();
        $originalData['fixed_discount'] = $original->fixed_discount?->toString();

        $newBilling = $billings->newEntity($originalData);
        // The customer and the contract are not on the form - the replacement belongs where the
        // billing it replaces did - but the record being written is a new one, and a new one is
        // asked for them.
        $newBilling = $billings->patchEntity($newBilling, $changes + [
            'customer_id' => $original->customer_id,
            'contract_id' => $original->contract_id,
        ]);
        $newBilling->service = $billings->Services->get($newBilling->service_id); // load associated service

        // Update original billing
        $original = $billings->patchEntity($original, [
            'billing_until' => $newBilling->billing_from->subDays(1),
        ]);

        // Persist both entities
        $saved = $billings->saveMany(
            [
                $original,
                $newBilling,
            ],
            $saveOptions + AuditTrail::batchOptions(),
        );

        if ($saved === false) {
            throw new BillingChangeRefused($newBilling->getErrors() ?: $original->getErrors());
        }

        if ($notifyCustomer) {
            $this->tellTheCustomer($original, $newBilling, $withoutLegislativeInformation);
        }

        return $newBilling;
    }

    /**
     * Queues the letter about the change.
     *
     * @param \App\Model\Entity\Billing $original The billing that ended.
     * @param \App\Model\Entity\Billing $newBilling The billing that took over.
     * @param bool $withoutLegislativeInformation Whether the letter leaves that part out.
     * @return void
     */
    private function tellTheCustomer(
        Billing $original,
        Billing $newBilling,
        bool $withoutLegislativeInformation,
    ): void {
        /** @var \App\Model\Table\CustomerMessagesTable $customerMessagesTable */
        $customerMessagesTable = $this->fetchTable(CustomerMessagesTable::class);

        $body = ServiceChangeMessageBuilder::buildEmailBody(
            // Customer and contract details
            customerName: $original->customer->name,
            contractNumber: $original->contract->number ?? '',
            installationAddress: $original->contract->installation_address->address ?? null,
            // Original billing details
            originalBillingName: $original->name,
            originalBillingSum: $original->sum->toFloat(),
            originalBillingTotalPrice: $original->total_price->toFloat(),
            // New billing details
            newBillingName: $newBilling->name,
            newBillingSum: $newBilling->sum->toFloat(),
            newBillingTotalPrice: $newBilling->total_price->toFloat(),
            // Other details
            newBillingFrom: (string)$newBilling->billing_from,
            versionWithoutLegislativeInformation: $withoutLegislativeInformation,
        );

        $subject = strtr(
            Settings::getString('core.emails.service_change.subject'),
            [
                '{new_billing_from}' => (string)$newBilling->billing_from,
                '{contract_number}' => $original->contract->number ?? '',
            ],
        );

        // Create customer message entity
        $customerMessage = $customerMessagesTable->newEmptyEntity();

        $customerMessage->type = CustomerMessageType::EmailContracts;
        $customerMessage->direction = CustomerMessageDirection::Outgoing;
        $customerMessage->body_format = CustomerMessageBodyFormat::Plaintext;
        $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

        $customerMessage->customer_id = $original->customer_id;
        $customerMessage->recipients = array_column($original->customer->billing_emails, 'email');
        $customerMessage->subject = $subject;
        $customerMessage->body = $body;

        // Persist customer message entity
        $customerMessagesTable->saveOrFail($customerMessage);
    }
}
