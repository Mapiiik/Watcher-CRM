<?php
declare(strict_types=1);

namespace Settings\ValueObject\Trait;

use Cake\Log\Log;
use Settings\Exception\SettingValueException;
use Throwable;

/**
 * What a type does about a shipped default that does not fit it.
 *
 * The defaults are declared in a configuration file that the service reads on the way up, so the
 * types are built on every request before anything else happens. A type that threw over one of them
 * took the whole application down - every page, including the settings page where the declaration
 * could have been put right - and it did so on one installation and not another, because what fits
 * can depend on where the machine stands.
 *
 * So a declaration that does not fit is written to the log and the type ships what it can. What a
 * person submits is another matter entirely: that is refused, out loud, on the form they typed it
 * into, which is where a refusal belongs and where it costs nothing.
 */
trait ShippedValueTrait
{
    /**
     * Say in the log that a declared default does not fit, and carry on.
     *
     * @param \Throwable $refused Why it does not fit.
     * @return void
     */
    protected static function sayTheShippedValueDoesNotFit(Throwable $refused): void
    {
        Log::error(sprintf(
            'A setting declared in the configuration does not fit the type it was declared with,'
            . ' so what could be kept of it is being used: %s (%s)',
            $refused->getMessage(),
            static::class,
        ));
    }

    /**
     * Keep a value the type could not make sense of, having said so.
     *
     * @param string $value The value as it was declared.
     * @param string $expected What the type wanted instead, for the log.
     * @return string
     */
    protected static function shipped(string $value, string $expected): string
    {
        self::sayTheShippedValueDoesNotFit(
            new SettingValueException(sprintf('%s was expected, not "%s"', $expected, $value)),
        );

        return $value;
    }
}
