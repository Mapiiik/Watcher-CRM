<?php
declare(strict_types=1);

namespace Settings\ValueObject\Type;

use Settings\Exception\SettingValueException;
use Settings\ValueObject\SettingType;
use Settings\ValueObject\SettingWidget;

/**
 * A number, either whole or given to a fixed number of decimal places.
 *
 * Undeclared, a numeric setting is a plain text field and will hold whatever is typed into it, so
 * a count of days can come back out of the database as a word. That is left alone where a value
 * only looks numeric; declaring a setting is how it is said that this one really is a number.
 *
 * A value too finely given is refused rather than rounded to fit - rounding would store a number
 * nobody asked for, and quietly.
 */
final readonly class NumberType implements SettingType
{
    /**
     * The value shipped when nothing is stored.
     *
     * @var float|int
     */
    private float|int $default;

    /**
     * @param float|int $default The value shipped when nothing is stored.
     * @param int|null $scale How many decimal places are allowed, or null for whole numbers.
     * @param string|null $hint What to tell the operator about the field.
     */
    private function __construct(
        float|int $default,
        private ?int $scale,
        private ?string $hint,
    ) {
        $this->default = $this->cast($default);
    }

    /**
     * A whole number.
     *
     * Name the arguments when declaring one - `ofInt(default: 5, hint: ...)` - so the declaration
     * says what each value is for and survives the parameters being changed.
     *
     * @param int $default The value shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     * @return self
     */
    public static function ofInt(int $default, ?string $hint = null): self
    {
        return new self($default, null, $hint);
    }

    /**
     * A number given to a fixed number of decimal places.
     *
     * Name the arguments when declaring one - `ofDecimal(default: 0.0, scale: 2, hint: ...)` - so
     * the declaration says what each value is for and survives the parameters being changed.
     *
     * @param float $default The value shipped when nothing is stored.
     * @param int $scale How many decimal places are allowed.
     * @param string|null $hint What to tell the operator about the field.
     * @return self
     */
    public static function ofDecimal(float $default, int $scale = 2, ?string $hint = null): self
    {
        return new self($default, $scale, $hint);
    }

    /**
     * @inheritDoc
     */
    public function default(): mixed
    {
        return $this->default;
    }

    /**
     * @inheritDoc
     */
    public function widget(): SettingWidget
    {
        return SettingWidget::Number;
    }

    /**
     * @inheritDoc
     */
    public function hint(): ?string
    {
        return $this->hint;
    }

    /**
     * @inheritDoc
     */
    public function formOptions(): array
    {
        return ['step' => $this->step()];
    }

    /**
     * @inheritDoc
     */
    public function toFormValue(mixed $value): mixed
    {
        // what was refused comes back as it was typed, so it can be corrected rather than retyped
        if (is_string($value)) {
            return $value;
        }

        if (!is_int($value) && !is_float($value)) {
            return '';
        }

        return (string)$value;
    }

    /**
     * @inheritDoc
     */
    public function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return $this->cast($value);
    }

    /**
     * How finely the number may be given, as the form field states it.
     *
     * @return string
     */
    private function step(): string
    {
        if ($this->scale === null || $this->scale < 1) {
            return '1';
        }

        return '0.' . str_repeat('0', $this->scale - 1) . '1';
    }

    /**
     * Check a value and return it as the number it was declared to be.
     *
     * A number written as text was still meant as a number; anything that would lose part of its
     * value is refused.
     *
     * @param mixed $value The value to cast.
     * @return float|int
     * @throws \Settings\Exception\SettingValueException When the value is not such a number.
     */
    private function cast(mixed $value): float|int
    {
        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            throw new SettingValueException(__d('settings', 'A number was expected.'));
        }

        $number = (float)$value;

        if ($this->scale === null) {
            if ($number !== floor($number)) {
                throw new SettingValueException(__d('settings', 'A whole number was expected.'));
            }

            return (int)$number;
        }

        if (round($number, $this->scale) !== $number) {
            throw new SettingValueException(__d(
                'settings',
                'At most {scale} decimal places were expected.',
                ['scale' => $this->scale],
            ));
        }

        return $number;
    }
}
