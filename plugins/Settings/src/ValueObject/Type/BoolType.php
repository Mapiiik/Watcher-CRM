<?php
declare(strict_types=1);

namespace Settings\ValueObject\Type;

use Settings\ValueObject\SettingType;
use Settings\ValueObject\SettingWidget;

/**
 * A switch that is either on or off.
 *
 * Undeclared, such a setting is drawn as a text field, and anything written into it that is not
 * empty - "false" included - comes back out of the database as true.
 *
 * It is edited as a choice of three rather than as a checkbox, because a checkbox has nowhere to
 * put the third answer: a ticked box and an unticked one are both an answer, leaving no way to say
 * "whatever was shipped" - which is what a blank field means everywhere else in the form.
 */
final readonly class BoolType implements SettingType
{
    /**
     * Name the arguments when declaring one - `new BoolType(default: true, hint: ...)` - so the
     * declaration says what each value is for and survives the parameters being changed.
     *
     * @param bool $default The value shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     */
    public function __construct(
        private bool $default,
        private ?string $hint = null,
    ) {
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
        return SettingWidget::TriState;
    }

    /**
     * @inheritDoc
     */
    public function hint(): ?string
    {
        return $this->hint;
    }

    /**
     * The three answers are named in the form rather than here - what they are called is the
     * template's business, and translating them is not a value object's.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function toFormValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        return $this->truth($value) ? '1' : '0';
    }

    /**
     * @inheritDoc
     */
    public function normalize(mixed $value): mixed
    {
        // the answer left unchosen, which is how the form asks for the shipped value
        if ($value === null || $value === '') {
            return null;
        }

        return $this->truth($value);
    }

    /**
     * Read a value that has been given as a switch position.
     *
     * A value that reached the database while the setting was undeclared was stored as text and may
     * say what it means in words.
     *
     * @param mixed $value The value to read.
     * @return bool
     */
    private function truth(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return !in_array(strtolower(trim($value)), ['0', 'false', 'no', 'off'], true);
        }

        return (bool)$value;
    }
}
