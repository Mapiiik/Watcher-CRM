<?php
declare(strict_types=1);

namespace WorkReports\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * Where a work report is on its way to the supervisor.
 */
enum WorkReportState: string implements EnumLabelInterface
{
    case Open = 'open';
    case Submitted = 'submitted';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Open => __d('work_reports', 'Open'),
            self::Submitted => __d('work_reports', 'Submitted'),
        };
    }
}
