<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * ServiceCriticalityLevel Enum
 */
enum ServiceCriticalityLevel: int implements EnumLabelInterface
{
    case Normal = 10;
    case Important = 20;
    case Critical = 30;

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Normal => __('Normal'),
            self::Important => __('Important'),
            self::Critical => __('Critical'),
        };
    }
}
