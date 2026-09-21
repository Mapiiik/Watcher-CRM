<?php
declare(strict_types=1);

namespace WorkReports\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * What kind of day a day is, which is what an on-call day is worth by.
 */
enum DayKind: string implements EnumLabelInterface
{
    case WorkingDay = 'working_day';
    case Weekend = 'weekend';
    case Holiday = 'holiday';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::WorkingDay => __d('work_reports', 'Working Day'),
            self::Weekend => __d('work_reports', 'Weekend'),
            self::Holiday => __d('work_reports', 'Public Holiday'),
        };
    }
}
