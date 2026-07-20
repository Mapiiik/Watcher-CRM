<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * CustomerMessagePurpose Enum
 *
 * Drives the bulk message wizard: each purpose knows which customer consent
 * and which contact routing flag it requires, and which recipient filters it
 * offers. Add a new purpose here to make it available in the wizard.
 */
enum CustomerMessagePurpose: int implements EnumLabelInterface
{
    case Billing = 10;
    case Outages = 20;
    case Commercial = 30;

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Billing => __('Billing message'),
            self::Outages => __('Outage notification'),
            self::Commercial => __('Commercial message'),
        };
    }

    /**
     * Customer column that must be true for the customer to be eligible
     * (unless the consent override is active).
     *
     * @return string
     */
    public function customerConsentField(): string
    {
        return match ($this) {
            self::Billing => 'agree_mailing_billing',
            self::Outages => 'agree_mailing_outages',
            self::Commercial => 'agree_mailing_commercial',
        };
    }

    /**
     * Email/Phone column that must be true for the contact to be used as a
     * recipient (unless the consent override is active).
     *
     * @return string
     */
    public function contactUseField(): string
    {
        return match ($this) {
            self::Billing => 'use_for_billing',
            self::Outages => 'use_for_outages',
            self::Commercial => 'use_for_commercial',
        };
    }

    /**
     * Settings key under `core.customer_messages.*` holding this purpose's
     * default compose template (subject / body_text).
     *
     * @return string
     */
    public function settingsKey(): string
    {
        return match ($this) {
            self::Billing => 'billing',
            self::Outages => 'outages',
            self::Commercial => 'commercial',
        };
    }

    /**
     * Default message type (channel) prefilled in the compose step. Always an
     * e-mail flavour matching the purpose; the operator can still change it.
     *
     * @return \App\Model\Enum\CustomerMessageType
     */
    public function defaultType(): CustomerMessageType
    {
        return match ($this) {
            self::Billing => CustomerMessageType::EmailInvoices,
            self::Outages => CustomerMessageType::EmailSupport,
            self::Commercial => CustomerMessageType::EmailContracts,
        };
    }

    /**
     * Ordered list of filter keys offered for this purpose.
     *
     * The keys must match filters registered in
     * {@see \App\BulkMessages\BulkRecipientFilterRegistry}.
     *
     * @return array<string>
     */
    public function filterKeys(): array
    {
        return match ($this) {
            self::Billing => [
                'label_ids',
                'not_label_ids',
                'registry_address_id',
                'access_point',
                'billed_contract',
            ],
            self::Outages => [
                'label_ids',
                'not_label_ids',
                'registry_address_id',
                'access_point',
                'active_services_contract',
            ],
            self::Commercial => [
                'label_ids',
                'not_label_ids',
                'active_services_contract',
            ],
        };
    }
}
