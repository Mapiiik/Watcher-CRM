<?php
declare(strict_types=1);

namespace Settings\ValueObject\Type;

use Cake\I18n\Date;
use DateTimeInterface;
use Settings\Exception\SettingValueException;
use Settings\ValueObject\SettingType;
use Settings\ValueObject\SettingWidget;
use Settings\ValueObject\Trait\ShippedValueTrait;

/**
 * A single day.
 *
 * Undeclared, a date is a plain text field and will hold whatever is typed into it, so a boundary
 * a check reads can come back out of the database as a word. Declaring the setting is how it is
 * said that this one really is a day, and it also buys the native date control - the widget names
 * the kind of editing, and the form builds it from there.
 *
 * The value is kept as `Y-m-d` text rather than as a date object: the type is met only by the
 * service, which puts a plain value back for everything below it to read and cache.
 * {@see \Settings\Utility\Settings::getDate()} is what hands a caller a real date.
 *
 * Only a whole `Y-m-d` is accepted - the shape the date control submits. A day that does not exist
 * is refused rather than rolled forward into the next month, which would store a day nobody asked
 * for, and quietly.
 */
final readonly class DateType implements SettingType
{
    use ShippedValueTrait;

    /**
     * The day shipped when nothing is stored, as `Y-m-d`.
     *
     * @var string
     */
    private string $default;

    /**
     * @param \Cake\I18n\Date|\DateTimeInterface|string $default The day shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     */
    private function __construct(
        Date|DateTimeInterface|string $default,
        private ?string $hint,
    ) {
        $this->default = self::canonicalDay($default) ?? self::shipped(
            $default instanceof Date || $default instanceof DateTimeInterface
                ? $default->format('Y-m-d')
                : $default,
            'a day in the form YYYY-MM-DD',
        );
    }

    /**
     * A day.
     *
     * Name the arguments when declaring one - `of(default: '2000-01-01', hint: ...)` - so the
     * declaration says what each value is for and survives the parameters being changed.
     *
     * @param \Cake\I18n\Date|\DateTimeInterface|string $default The day shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     * @return self
     */
    public static function of(Date|DateTimeInterface|string $default, ?string $hint = null): self
    {
        return new self($default, $hint);
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
        return SettingWidget::Date;
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
        return [];
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

        if (!$value instanceof DateTimeInterface && !$value instanceof Date) {
            return '';
        }

        return $value->format('Y-m-d');
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
     * The day a value names, as `Y-m-d`, or null where it names none.
     *
     * A list of days is checked by the same rule, item by item, so the rule lives here rather than
     * being written twice. {@see \Settings\ValueObject\Type\ListType::ofDates()}
     *
     * @param mixed $value The value to read.
     * @return string|null
     */
    public static function canonicalDay(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface || $value instanceof Date) {
            return $value->format('Y-m-d');
        }

        if (!is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
            return null;
        }

        // Checked against the calendar itself rather than by parsing and looking at what came
        // back. Parsing reads the day in the machine's timezone, and before about 1884 a zone's
        // offset was the town's own solar time - which ICU rounds to whole minutes and the
        // timezone database does not. Those few seconds are enough to hand back the day before:
        // 1800-01-01 under Europe/Zagreb parses as 1799-12-31, so an installation running in
        // that zone rejected a day every other installation accepted.
        return checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]) ? $value : null;
    }

    /**
     * Check a value and return the day it names, as `Y-m-d`.
     *
     * @param mixed $value The value to read.
     * @return string
     * @throws \Settings\Exception\SettingValueException When the value does not name a day.
     */
    private function cast(mixed $value): string
    {
        return self::canonicalDay($value)
            ?? throw new SettingValueException(
                __d('settings', 'A day in the form YYYY-MM-DD was expected.'),
            );
    }
}
