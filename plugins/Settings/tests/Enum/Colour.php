<?php
declare(strict_types=1);

namespace Settings\Test\Enum;

use Settings\ValueObject\SettingChoices;

/**
 * An enum for the tests to offer as the answers to a setting.
 *
 * The applications carry a trait that writes `options()` off the labels an enum already has; this
 * one writes it out, so the plugin's own tests do not lean on the application around them.
 */
enum Colour: string implements SettingChoices
{
    case RED = 'red';
    case BLUE = 'blue';

    /**
     * @inheritDoc
     */
    public static function options(): array
    {
        return ['red' => 'Red', 'blue' => 'Blue'];
    }
}
