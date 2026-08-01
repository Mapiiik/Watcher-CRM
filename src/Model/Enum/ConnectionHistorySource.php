<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * ConnectionHistorySource Enum
 *
 * The system a connection history interval was derived from. Further sources
 * may be added by implementing \App\Service\ConnectionHistory\SourceInterface
 * and registering it under ConnectionHistory.sources.
 */
enum ConnectionHistorySource: string implements EnumLabelInterface
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
