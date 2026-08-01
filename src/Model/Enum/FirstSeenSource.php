<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * FirstSeenSource Enum
 *
 * Says how the first_seen of a connection history interval came to be, which
 * decides whether it may be read as an exact moment.
 */
enum FirstSeenSource: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    /**
     * Taken from the first session observed within the interval, exact.
     */
    case Session = 'session';

    /**
     * Taken from the oldest session still present in the source at the time the
     * history was first built. The connection may well be older than that, the
     * evidence for it has already been purged.
     */
    case InitialLoad = 'initial-load';

    /**
     * The interval was opened because the account was moved to another customer
     * or contract. Accurate to the run of the update at worst, usually to the
     * minute the account was edited.
     */
    case AccountChange = 'account-change';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Session => __('First session'),
            self::InitialLoad => __('Oldest available record'),
            self::AccountChange => __('Account change'),
        };
    }

    /**
     * Indicates whether first_seen may be presented as an exact moment.
     *
     * @return bool
     */
    public function isExact(): bool
    {
        return $this === self::Session;
    }
}
