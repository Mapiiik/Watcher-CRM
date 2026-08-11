<?php
declare(strict_types=1);

namespace Settings\ValueObject;

/**
 * The declared type of a single setting.
 *
 * A type is written into the defaults in place of the value it describes. The service collects the
 * types while reading the defaults and puts the plain value back, so nothing below the service
 * constructor - neither the cache, nor a template, nor a caller of `Settings::get()` - ever meets
 * one.
 *
 * Declaring a setting also says it is a leaf: an overlay replaces it whole instead of being merged
 * into it key by key.
 */
interface SettingType
{
    /**
     * The value shipped when nothing is stored.
     *
     * @return mixed
     */
    public function default(): mixed;

    /**
     * The control the value is edited in.
     *
     * @return \Settings\ValueObject\SettingWidget
     */
    public function widget(): SettingWidget;

    /**
     * What to tell the operator about the field, if anything.
     *
     * @return string|null
     */
    public function hint(): ?string;

    /**
     * What else the control needs to be built, beyond its kind and its value.
     *
     * These are handed to the form helper as they are, so a type can settle things the template
     * has no way of knowing - how finely a number may be given, how tall a box should be - without
     * the template having to ask what kind of type it is holding.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array;

    /**
     * Turn a stored value into what the control shows.
     *
     * A value that failed to normalize is handed back as it was submitted, so a refused form keeps
     * what was typed into it.
     *
     * @param mixed $value The stored value, or what was submitted for it.
     * @return mixed
     */
    public function toFormValue(mixed $value): mixed;

    /**
     * Turn what was submitted into the value to store.
     *
     * Returning null says nothing was submitted and the default should answer.
     *
     * @param mixed $value What was submitted.
     * @return mixed
     * @throws \Settings\Exception\SettingValueException When the value cannot be stored as this type.
     */
    public function normalize(mixed $value): mixed;
}
