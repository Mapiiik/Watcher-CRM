<?php
declare(strict_types=1);

namespace Bookkeeping\Provider;

use App\Model\Entity\Customer;
use Cake\Log\Log;
use Settings\Utility\Settings;

/**
 * Which partner cards in the accounting system are ours to write.
 *
 * A card is not ours where the customer is not to be synchronized, or where their number lies below
 * the one the cards are ours from: the cards of those customers were made by hand, without our
 * external ID, and writing ours would set a second card beside them.
 *
 * The provider using it names its settings by `SETTINGS_ROOT`.
 */
trait PartnerCardsTrait
{
    /**
     * The customer number the partner cards are ours from, 0 where they all are.
     *
     * @return int
     */
    public static function minCustomerNumber(): int
    {
        return (int)Settings::get(static::SETTINGS_ROOT . '.customers.min_customer_number', 0);
    }

    /**
     * Whether the customer's partner card is ours to write.
     *
     * @param \App\Model\Entity\Customer $customer Customer.
     * @return bool
     */
    public static function writesPartnerCardOf(Customer $customer): bool
    {
        return self::whyPartnerCardIsLeftAlone($customer, static::minCustomerNumber()) === null;
    }

    /**
     * The customers whose partner cards are ours to write. The others are left out and logged,
     * which is all that happens to them: an invoice for them still goes out, only the card is
     * not written.
     *
     * @param list<\App\Model\Entity\Customer> $customers Customers.
     * @return list<\App\Model\Entity\Customer>
     */
    protected static function partnersToWrite(array $customers): array
    {
        $minCustomerNumber = static::minCustomerNumber();

        return array_values(array_filter(
            $customers,
            function (Customer $customer) use ($minCustomerNumber): bool {
                $reason = self::whyPartnerCardIsLeftAlone($customer, $minCustomerNumber);
                if ($reason === null) {
                    return true;
                }

                Log::info('Partner sync skipped for customer ' . $customer->number . ': ' . $reason . '.');

                return false;
            },
        ));
    }

    /**
     * Why the customer's partner card is not ours to write, or null where it is.
     *
     * @param \App\Model\Entity\Customer $customer Customer.
     * @param int $minCustomerNumber The customer number the cards are ours from.
     * @return string|null
     */
    private static function whyPartnerCardIsLeftAlone(Customer $customer, int $minCustomerNumber): ?string
    {
        if (!$customer->sync_to_accounting) {
            return 'synchronization to the accounting system is turned off';
        }

        if ((int)$customer->number < $minCustomerNumber) {
            return 'the partner cards are ours from customer number ' . $minCustomerNumber;
        }

        return null;
    }
}
