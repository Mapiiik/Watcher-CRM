<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

/**
 * Which fields of which records a snapshot carries.
 *
 * One definition, read in both directions: the builder takes these off the live records and the
 * hydration puts them back on unsaved ones. Two hand-written methods would part company at the
 * first field somebody added to only one of them.
 *
 * What is kept is the plain fields, not what the entities work out from them. A contract knows how
 * to find its billing address among the customer's, an address knows how to write itself out, and
 * a billing knows what it comes to - so keeping the customer's addresses is enough, and keeping
 * the worked-out answers as well would only be a second place for them to be wrong. The one
 * exception is the address ranges, which are not worked out but asked of another system.
 */
final class SnapshotShape
{
    /**
     * The contract itself.
     *
     * @var array<string>
     */
    public const CONTRACT = [
        'id',
        'number',
        'subscriber_verification_code',
        'installation_date',
        'termination_date',
        'activation_fee',
        'activation_fee_with_obligation',
        'vip',
    ];

    /**
     * What kind of contract it is; the activation fee falls back to it.
     *
     * @var array<string>
     */
    public const SERVICE_TYPE = [
        'id',
        'name',
        'activation_fee',
        'activation_fee_with_obligation',
        'have_equipments',
        'have_ip_addresses',
        'have_radius_accounts',
        'normally_with_borrowed_equipment',
    ];

    /**
     * Who the contract is with.
     *
     * @var array<string>
     */
    public const CUSTOMER = [
        'id',
        'nid',
        'title',
        'first_name',
        'last_name',
        'suffix',
        'company',
        'date_of_birth',
        'identity_number',
        'identity_card_number',
        'vat_number',
    ];

    /**
     * The VAT the prices are quoted under.
     *
     * @var array<string>
     */
    public const ACCOUNTING_PROFILE = [
        'id',
        'name',
        'vat_rate',
        'reverse_charge',
    ];

    /**
     * An address, in the pieces it is written from.
     *
     * @var array<string>
     */
    public const ADDRESS = [
        'id',
        'type',
        'title',
        'first_name',
        'last_name',
        'suffix',
        'company',
        'street',
        'number',
        'number_type',
        'entrance',
        'unit',
        'city',
        'zip',
        'country_id',
    ];

    /**
     * One of the customer's e-mail addresses.
     *
     * @var array<string>
     */
    public const EMAIL = [
        'id',
        'email',
        'use_for_billing',
    ];

    /**
     * One of the customer's telephone numbers.
     *
     * @var array<string>
     */
    public const PHONE = [
        'id',
        'phone',
        'use_for_billing',
    ];

    /**
     * A version of the contract.
     *
     * @var array<string>
     */
    public const VERSION = [
        'id',
        'contract_id',
        'valid_from',
        'valid_until',
        'obligation_until',
        'conclusion_date',
        'number_of_amendments',
    ];

    /**
     * One line of what is billed for.
     *
     * @var array<string>
     */
    public const BILLING = [
        'id',
        'billing_from',
        'billing_until',
        'text',
        'quantity',
        'price',
        'fixed_discount',
        'percentage_discount',
        'separate_invoice',
    ];

    /**
     * What the line is for, off the price list.
     *
     * @var array<string>
     */
    public const SERVICE = [
        'id',
        'name',
        'price',
    ];

    /**
     * The speeds and limits the summary quotes as a regulatory figure.
     *
     * @var array<string>
     */
    public const QUEUE = [
        'id',
        'name',
        'caption',
        'speed_down',
        'speed_up',
        'speed_down_common',
        'speed_up_common',
        'speed_down_minimum',
        'speed_up_minimum',
        'fup_limit',
        'data_limit',
        'overlimit_fragment',
        'overlimit_cost',
        'cto_category',
    ];

    /**
     * A piece of equipment on the contract.
     *
     * @var array<string>
     */
    public const EQUIPMENT = [
        'id',
        'serial_number',
    ];

    /**
     * What kind of equipment it is, and what it costs.
     *
     * @var array<string>
     */
    public const EQUIPMENT_TYPE = [
        'id',
        'name',
        'price',
        'price_with_obligation',
    ];

    /**
     * An IP address assigned to the contract.
     *
     * @var array<string>
     */
    public const IP_ADDRESS = [
        'id',
        'ip_address',
        'type_of_use',
    ];

    /**
     * The range an address sits in, as another system answered it.
     *
     * @var array<string>
     */
    public const IP_ADDRESS_RANGE = [
        'network',
        'gateway',
    ];

    /**
     * A network assigned to the contract.
     *
     * @var array<string>
     */
    public const IP_NETWORK = [
        'id',
        'ip_network',
        'type_of_use',
    ];
}
