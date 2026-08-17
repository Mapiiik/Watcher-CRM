<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;
use RuntimeException;

/**
 * Why a customer's invoice address cannot be told from their addresses.
 *
 * Not persisted - it is worked out from how many addresses of each type a customer has,
 * against the order in {@see \App\Model\Enum\AddressType::billingFallback()}.
 */
enum BillingAddressProblem: string implements EnumLabelInterface
{
    /**
     * Nothing the fallback accepts, so the invoice carries no address at all.
     */
    case Missing = 'missing';

    /**
     * More than one billing address, so one of them is picked arbitrarily.
     */
    case AmbiguousBilling = 'ambiguous_billing';

    /**
     * No billing address and more than one permanent address.
     */
    case AmbiguousPermanent = 'ambiguous_permanent';

    /**
     * Neither, and more than one installation address - the usual case for a customer with
     * several sites who was never given a billing address of their own.
     */
    case AmbiguousInstallation = 'ambiguous_installation';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Missing => __('No address to invoice to'),
            self::AmbiguousBilling => __('Several billing addresses'),
            self::AmbiguousPermanent => __('Several permanent addresses'),
            self::AmbiguousInstallation => __('Several installation addresses'),
        };
    }

    /**
     * Whether the invoice goes out with no address at all, as against one that was picked
     * arbitrarily. The listings put these first.
     *
     * @return bool
     */
    public function isMissing(): bool
    {
        return $this === self::Missing;
    }

    /**
     * The problem that follows from how many addresses of each type a customer has.
     *
     * The types are walked in the fallback order rather than named one by one, so that a
     * change to that order carries here on its own.
     *
     * @param array<string, int> $counts How many addresses the customer has, keyed by the
     *   address type's name.
     * @return self|null Null where the invoice address is beyond doubt.
     */
    public static function fromCounts(array $counts): ?self
    {
        foreach (AddressType::billingFallback() as $type) {
            $total = $counts[$type->name] ?? 0;

            if ($total === 0) {
                continue;
            }

            // the first type the customer has anything of decides the invoice address, so
            // it is the only one whose count can still make it doubtful
            return $total > 1 ? self::forType($type) : null;
        }

        return self::Missing;
    }

    /**
     * The problem that stands for too many addresses of the given type.
     *
     * Only the types the invoice address is looked for in have one. A type added to
     * {@see \App\Model\Enum\AddressType::billingFallback()} without a case here has to say
     * so rather than be folded into one of the others and reported as the wrong thing.
     *
     * @param \App\Model\Enum\AddressType $type The type that has more than one address.
     * @return self
     * @throws \RuntimeException Where the type has no problem of its own.
     */
    private static function forType(AddressType $type): self
    {
        return match ($type) {
            AddressType::Billing => self::AmbiguousBilling,
            AddressType::Permanent => self::AmbiguousPermanent,
            AddressType::Installation => self::AmbiguousInstallation,
            default => throw new RuntimeException(
                sprintf('Address type %s is not one an invoice address is looked for in.', $type->name),
            ),
        };
    }
}
