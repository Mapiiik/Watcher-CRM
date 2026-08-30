<?php
declare(strict_types=1);

namespace Settings\ValueObject;

/**
 * An enum that may be offered as the answer to a setting.
 *
 * The plugin cannot reach into the application it is installed in, so it has to say for itself what
 * it needs of an enum: the answers, by the value each is stored as. Writing the method is nobody's
 * job - the applications carry a trait that does it off the labels the enum already has - so naming
 * this interface is all an enum does to become a field in the settings form.
 */
interface SettingChoices
{
    /**
     * The answers on offer, as stored value to what it is called.
     *
     * @return array<string, string>
     */
    public static function options(): array;
}
