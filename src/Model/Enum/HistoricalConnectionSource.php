<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * HistoricalConnectionSource Enum
 *
 * The system a historical connection was derived from. Further sources
 * may be added by implementing \App\Service\HistoricalConnections\SourceInterface
 * and registering it under HistoricalConnections.sources.
 */
enum HistoricalConnectionSource: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case Radius = 'radius';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Radius => __('RADIUS'),
        };
    }

    /**
     * What the source_reference of an interval actually names.
     *
     * Kept here rather than in the templates so a new source brings its own
     * wording along and nothing showing a reference has to be touched.
     *
     * @return string
     */
    public function referenceLabel(): string
    {
        return match ($this) {
            self::Radius => __('RADIUS Account'),
        };
    }
}
