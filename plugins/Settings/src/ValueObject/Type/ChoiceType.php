<?php
declare(strict_types=1);

namespace Settings\ValueObject\Type;

use BackedEnum;
use Settings\Exception\SettingValueException;
use Settings\ValueObject\SettingChoices;
use Settings\ValueObject\SettingType;
use Settings\ValueObject\SettingWidget;

/**
 * One answer out of a few that are named in advance.
 *
 * Undeclared, such a setting is a text field into which the exact spelling of an answer has to be
 * typed, and a spelling nobody recognises is only found out about wherever the value is read - by
 * which time the form that could have put it right is long gone. Declared, the answers are offered
 * to choose from and anything else is refused on the way in.
 *
 * The answers come from an enum rather than from a list written out here, so that the code reading
 * the setting and the form offering it cannot come to disagree about what the answers are.
 */
final readonly class ChoiceType implements SettingType
{
    /**
     * Name the arguments when declaring one - `new ChoiceType(default: Schedule::Monthly, hint: ...)`
     * - so the declaration says what each value is for and survives the parameters being changed.
     *
     * The default names the enum as well as the answer, which is why there is no second argument
     * for it: an answer that is not one of the offered ones cannot be declared by accident.
     *
     * @param \Settings\ValueObject\SettingChoices&\BackedEnum $default The answer shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     */
    public function __construct(
        private SettingChoices&BackedEnum $default,
        private ?string $hint = null,
    ) {
    }

    /**
     * The stored value rather than the case itself: nothing below the service is meant to meet a
     * type, and the cache would have to carry an object it cannot hold.
     *
     * @return mixed
     */
    public function default(): mixed
    {
        return $this->default->value;
    }

    /**
     * @inheritDoc
     */
    public function widget(): SettingWidget
    {
        return SettingWidget::Choice;
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
        return ['options' => $this->default::options()];
    }

    /**
     * @inheritDoc
     */
    public function toFormValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_string($value) && !is_int($value)) {
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

        // the answer left unchosen, which is how the form asks for the shipped value
        if ($value === null || $value === '') {
            return null;
        }

        $chosen = is_string($value) || is_int($value) ? $this->choice($value) : null;

        if ($chosen === null) {
            throw new SettingValueException(__d('settings', 'One of the answers on offer was expected.'));
        }

        return $chosen->value;
    }

    /**
     * Read a value that has been given as one of the answers.
     *
     * @param string|int $value The value to read.
     * @return \BackedEnum|null The answer, or null if it is not one of them.
     */
    private function choice(int|string $value): ?BackedEnum
    {
        // the form hands every answer back as text, and an enum kept as numbers will not take that
        if (is_string($value) && is_int($this->default->value)) {
            $number = filter_var($value, FILTER_VALIDATE_INT);

            if ($number === false) {
                return null;
            }

            $value = $number;
        }

        return $this->default::tryFrom($value);
    }
}
