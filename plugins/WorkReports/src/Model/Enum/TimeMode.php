<?php
declare(strict_types=1);

namespace WorkReports\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * How an item of a given type states its time.
 */
enum TimeMode: string implements EnumLabelInterface
{
    // from and until, as many a day as it takes - a break is the gap between two
    case Range = 'range';
    // the day as such, with no time
    case WholeDay = 'whole_day';
    // either of the two, chosen on the item
    case Either = 'either';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Range => __d('work_reports', 'From and Until'),
            self::WholeDay => __d('work_reports', 'Whole Day'),
            self::Either => __d('work_reports', 'From and Until, or Whole Day'),
        };
    }
}
