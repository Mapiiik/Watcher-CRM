<?php
declare(strict_types=1);

namespace Bookkeeping\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;
use Settings\ValueObject\SettingChoices;

/**
 * When invoicing runs, and so which period it has settled.
 *
 * Nothing in the data says which months have been invoiced - invoicing reads the billings for a
 * month and writes nothing back - so this is the only thing left to ask, and it differs between
 * installations. It is what tells a billing which of its dates may no longer be moved.
 *
 * The stored values are the ones the console command has always taken, because a cron line naming
 * one of them is what actually runs.
 */
enum InvoicingSchedule: string implements EnumLabelInterface, SettingChoices
{
    use EnumOptionsTrait;

    /**
     * Where the installation says which of these it does.
     *
     * Kept here rather than with either reader, because the command that runs on it and the
     * billings that are held against it have to be reading the same answer.
     *
     * @var string
     */
    public const SETTINGS_PATH = 'bookkeeping.invoices.issuing.schedule';

    case PREV_MONTH_ON_FIRST = 'prev-month-on-first';
    case CURRENT_MONTH_ON_LAST = 'current-month-on-last';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::PREV_MONTH_ON_FIRST => __d('bookkeeping', 'On the first day of the month that follows'),
            self::CURRENT_MONTH_ON_LAST => __d('bookkeeping', 'On the last day of the month it covers'),
        };
    }
}
