<?php
declare(strict_types=1);

namespace Bookkeeping\Model\Enum\Trait;

use Cake\Database\Type\EnumLabelInterface;
use LogicException;

trait EnumOptionsTrait
{
    /**
     * Return options list for backed enums with labels.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (static::cases() as $case) {
            /** @phpstan-ignore-next-line instanceof.alwaysTrue */
            if (!$case instanceof EnumLabelInterface) {
                throw new LogicException(
                    sprintf('%s must implement EnumLabelInterface.', static::class),
                );
            }

            $options[(string)$case->value] = $case->label();
        }

        return $options;
    }
}
