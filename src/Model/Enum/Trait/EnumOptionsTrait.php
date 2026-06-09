<?php
declare(strict_types=1);

namespace App\Model\Enum\Trait;

/**
 * Provides an options() helper for backed enums that implement
 * {@see \Cake\Database\Type\EnumLabelInterface}.
 *
 * @phpstan-require-implements \Cake\Database\Type\EnumLabelInterface
 */
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
            $options[(string)$case->value] = $case->label();
        }

        return $options;
    }
}
