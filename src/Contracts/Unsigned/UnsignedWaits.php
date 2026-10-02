<?php
declare(strict_types=1);

namespace App\Contracts\Unsigned;

use Settings\Utility\Settings;

/**
 * How long a running service may go unsigned before something happens about it.
 *
 * Two days are counted at once and the later of them is what the wait runs to: the service has to
 * have been running for a while, and what it runs on has to have been in effect for a while. Either
 * alone catches a contract that is merely new.
 *
 * They are read here rather than where they are used, because they were being read in four places -
 * the command that writes to the customer, the run that cuts the service off, the check that lists
 * what is overdue and the card that counts it - each carrying its own copy of what the settings say
 * and its own fallback. Four copies of a disconnection deadline is three too many: the caption on
 * the listing has to mean the same number of days the nightly run acts on.
 */
final readonly class UnsignedWaits
{
    /**
     * Where the settings say how long each wait is.
     */
    private const SETTINGS_PATH = 'core.contracts.paperwork.unsigned';

    /**
     * The waits, where the settings name none.
     */
    private const NOTIFY_AFTER_ANCHOR_DAYS = 5;

    private const NOTIFY_AFTER_START_DAYS = 10;

    private const BLOCK_AFTER_ANCHOR_DAYS = 10;

    private const BLOCK_AFTER_START_DAYS = 20;

    /**
     * @param int $after_anchor Days after the day the wait is counted from - the installation, or
     *   the sending, as {@see \App\Model\Enum\UnsignedDeadlineAnchor} has it.
     * @param int $after_start Days after what is running took effect.
     */
    public function __construct(public int $after_anchor = 0, public int $after_start = 0)
    {
    }

    /**
     * No wait at all: everything that has taken effect and come back unsigned.
     *
     * This is what a listing asks, because the three things that can be done about an unsigned
     * service are worth seeing before the last of them is due.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self();
    }

    /**
     * How long before the customer is written to.
     *
     * @return self
     */
    public static function beforeNotifying(): self
    {
        return self::fromSettings('notifications', self::NOTIFY_AFTER_ANCHOR_DAYS, self::NOTIFY_AFTER_START_DAYS);
    }

    /**
     * How long before the service is cut off.
     *
     * @return self
     */
    public static function beforeBlocking(): self
    {
        return self::fromSettings('blocking', self::BLOCK_AFTER_ANCHOR_DAYS, self::BLOCK_AFTER_START_DAYS);
    }

    /**
     * @param string $kind Settings block the pair is kept under ("notifications", "blocking").
     * @param int $anchor Days after the anchor, where the settings say nothing.
     * @param int $start Days after it took effect, where the settings say nothing.
     * @return self
     */
    private static function fromSettings(string $kind, int $anchor, int $start): self
    {
        return new self(
            self::days(sprintf('%s.%s.after_anchor_days', self::SETTINGS_PATH, $kind), $anchor),
            self::days(sprintf('%s.%s.after_start_days', self::SETTINGS_PATH, $kind), $start),
        );
    }

    /**
     * A wait in days, or the fallback where what is stored is not a number at all.
     *
     * @param string $path Where the settings keep it.
     * @param int $default What to use where they say nothing.
     * @return int
     */
    private static function days(string $path, int $default): int
    {
        $value = Settings::get($path, $default);

        return is_numeric($value) ? (int)$value : $default;
    }
}
