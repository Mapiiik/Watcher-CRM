<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Date;
use Cake\I18n\Number;
use Settings\Utility\Settings;

final class ServiceChangeMessageBuilder
{
    /**
     * Builds the email body for service change notifications.
     *
     * @param string $customerName Name of the customer
     * @param string $contractNumber Contract number
     * @param string|null $installationAddress Installation address (optional)
     * @param string $originalBillingName Name of the original billing
     * @param float $originalBillingSum Sum of the original billing
     * @param float $originalBillingTotalPrice Total price of the original billing
     * @param string $newBillingName Name of the new billing
     * @param float $newBillingSum Sum of the new billing
     * @param float $newBillingTotalPrice Total price of the new billing
     * @param string $newBillingFrom Date when the new billing starts
     * @param bool $versionWithoutLegislativeInformation Whether to use a version without legislative information
     * @return string The constructed email body
     */
    public static function buildEmailBody(
        // Customer and contract details
        string $customerName,
        string $contractNumber,
        ?string $installationAddress,
        // Original billing details
        string $originalBillingName,
        float $originalBillingSum,
        float $originalBillingTotalPrice,
        // New billing details
        string $newBillingName,
        float $newBillingSum,
        float $newBillingTotalPrice,
        // Other details
        string $newBillingFrom,
        bool $versionWithoutLegislativeInformation,
    ): string {
        $billingDate = (new Date($newBillingFrom))->lastOfMonth();

        $lines = [];

        // Greeting
        $lines[] = Settings::getString('core.emails.service_change.greeting');
        $lines[] = '';

        // Intro
        $lines[] = strtr(
            Settings::getString('core.emails.service_change.intro'),
            [
                '{new_billing_from}' => $newBillingFrom,
                '{contract_number}' => $contractNumber,
                '{installation_address_info}' =>
                    $installationAddress
                        ? strtr(
                            Settings::getString('core.emails.service_change.installation_address_info'),
                            ['{installation_address}' => $installationAddress],
                        )
                        : '',
            ],
        );
        $lines[] = '';

        // Current tariff label
        $lines[] = Settings::getString('core.emails.service_change.current_tariff_label');

        // Current tariff details
        if ($originalBillingSum > $originalBillingTotalPrice) {
            $lines[] = strtr(
                Settings::getString('core.emails.service_change.current_tariff_discounted'),
                [
                    '{original_billing_name}' => $originalBillingName,
                    '{original_billing_total_price}' =>
                        Number::currency($originalBillingTotalPrice),
                ],
            );
        } else {
            $lines[] = strtr(
                Settings::getString('core.emails.service_change.current_tariff'),
                [
                    '{original_billing_name}' => $originalBillingName,
                    '{original_billing_total_price}' =>
                        Number::currency($originalBillingTotalPrice),
                ],
            );
        }
        $lines[] = '';

        // New tariff label
        $lines[] = Settings::getString('core.emails.service_change.new_tariff_label');

        // New tariff details
        if ($newBillingSum > $newBillingTotalPrice) {
            $lines[] = strtr(
                Settings::getString('core.emails.service_change.new_tariff_discounted'),
                [
                    '{new_billing_name}' => $newBillingName,
                    '{new_billing_total_price}' =>
                        Number::currency($newBillingTotalPrice),
                    '{new_billing_sum}' =>
                        Number::currency($newBillingSum),
                ],
            );
        } else {
            $lines[] = strtr(
                Settings::getString('core.emails.service_change.new_tariff'),
                [
                    '{new_billing_name}' => $newBillingName,
                    '{new_billing_total_price}' =>
                        Number::currency($newBillingTotalPrice),
                ],
            );
        }
        $lines[] = '';

        // Historical discount information
        if ($originalBillingSum > $originalBillingTotalPrice && $newBillingSum > $newBillingTotalPrice) {
            $lines[] = Settings::getString('core.emails.service_change.historical_discount');
            $lines[] = '';
        }

        // First changed payment information
        if ($originalBillingTotalPrice === $newBillingTotalPrice) {
            $lines[] = Settings::getString('core.emails.service_change.no_change');
        } else {
            $lines[] = strtr(
                Settings::getString('core.emails.service_change.first_payment'),
                [
                    '{billing_date}' => (string)$billingDate,
                    '{billing_date_plus_10}' => (string)$billingDate->addDays(10),
                ],
            );
        }
        $lines[] = '';

        // Legislative informations
        if (!$versionWithoutLegislativeInformation) {
            $lines[] = strtr(
                Settings::getString('core.emails.service_change.price_list'),
                [
                    '{price_list_url}' =>
                        Settings::getString('core.company.price_list_url'),
                ],
            );
            $lines[] = '';
            $lines[] = Settings::getString('core.emails.service_change.legislative_information');
            $lines[] = '';
        }

        // Closing
        $lines[] = Settings::getString('core.emails.service_change.closing');
        $lines[] = '';

        // Company information
        $lines[] = Settings::getString('core.company.name');
        $lines[] = Settings::getString('core.company.address');
        $lines[] = __('Email') . ': ' . Settings::getString('core.company.contracts.email');
        $lines[] = __('Phone') . ': ' . Settings::getString('core.company.contracts.phone');

        return implode(PHP_EOL, $lines);
    }
}
