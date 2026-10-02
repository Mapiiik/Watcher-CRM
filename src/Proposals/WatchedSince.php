<?php
declare(strict_types=1);

namespace App\Proposals;

use Cake\I18n\Date;
use Settings\Utility\Settings;

/**
 * The day from which the paperwork is somebody's work at all.
 *
 * What an import left behind is not work anybody is going to do, and writing to a customer about
 * a contract from before the office kept the papers this way would be worse than leaving it. The
 * line is the office's answer rather than the code's, and it slides forward as the backlog is
 * either dealt with or given up on.
 *
 * Every check of the family is held to it, and all of them measure it the same way: against the
 * day the record speaks about - a version's start, a proposal's effective date, the day a
 * service began to be charged for - never against when somebody sent or signed something. One
 * reading, so that the same contract cannot be inside one check's reach and outside another's.
 */
final class WatchedSince
{
    /**
     * Where the two agendas keep it.
     */
    private const CONTRACTS_PATH = 'core.contracts.paperwork.consider_from';

    private const CUSTOMERS_PATH = 'core.customers.paperwork.consider_from';

    /**
     * The day it starts from, if nothing says otherwise.
     */
    private const DEFAULT = '2026-01-01';

    /**
     * @return \Cake\I18n\Date
     */
    public static function contracts(): Date
    {
        return self::at(self::CONTRACTS_PATH);
    }

    /**
     * @return \Cake\I18n\Date
     */
    public static function customers(): Date
    {
        return self::at(self::CUSTOMERS_PATH);
    }

    /**
     * @param string $path Where the settings keep the day.
     * @return \Cake\I18n\Date
     */
    private static function at(string $path): Date
    {
        return Settings::getDate($path, self::DEFAULT) ?? new Date(self::DEFAULT);
    }
}
