<?php
declare(strict_types=1);

namespace App\Contracts;

use Cake\I18n\Date;
use Settings\Utility\Settings;

/**
 * How long a minimum term usually runs, and the day it therefore runs to.
 *
 * Offered rather than imposed: the operator says whether there is a term at all and may write any
 * day they like. What this answers is the day that is offered to save them the typing, which is
 * why it lives in one place - the form a version is created on and the form papers are drawn up on
 * would otherwise offer two different days for the same agreement.
 *
 * The last day of the term is the day before the same date months later: a term agreed from the
 * first of October runs to the last of September, not to the first. The same shape the billings
 * use where one stops because another starts.
 */
final class TheUsualTerm
{
    /**
     * How many months, by default.
     *
     * @var int
     */
    private const MONTHS = 24;

    /**
     * The day a minimum term agreed from the given day usually runs to.
     *
     * @param \Cake\I18n\Date $from The day the agreement takes effect.
     * @return \Cake\I18n\Date
     */
    public static function from(Date $from): Date
    {
        return $from->addMonths(self::months())->subDays(1);
    }

    /**
     * How long it runs, in months.
     *
     * @return int
     */
    public static function months(): int
    {
        return (int)Settings::get('core.contracts.obligation_months', self::MONTHS);
    }
}
