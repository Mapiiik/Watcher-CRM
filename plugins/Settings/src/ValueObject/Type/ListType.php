<?php
declare(strict_types=1);

namespace Settings\ValueObject\Type;

use JsonException;
use Settings\Exception\SettingValueException;
use Settings\ValueObject\SettingType;
use Settings\ValueObject\SettingWidget;

/**
 * A list of values of one type, edited as JSON.
 *
 * JSON keeps the difference between 5 and "5" and can spell an empty list out - neither of which
 * a row of plain text fields could do, since a field left blank has to mean "use the default".
 *
 * An empty field therefore still falls back to the default; a list with no items is written as
 * `[]`.
 */
final readonly class ListType implements SettingType
{
    /**
     * The list shipped when nothing is stored.
     *
     * @var array<int, float|int|string>
     */
    private array $default;

    /**
     * @param array<int, mixed> $default The list shipped when nothing is stored.
     * @param string $itemType What the items are: `string`, `int` or `float`.
     * @param string|null $hint What to tell the operator about the field.
     */
    private function __construct(
        array $default,
        private string $itemType,
        private ?string $hint,
    ) {
        $this->default = $this->castList($default);
    }

    /**
     * A list of strings.
     *
     * Name the arguments when declaring one - `ofStrings(default: [...], hint: ...)` - so the
     * declaration says what each value is for and survives the parameters being changed.
     *
     * @param array<int, mixed> $default The list shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     * @return self
     */
    public static function ofStrings(array $default, ?string $hint = null): self
    {
        return new self($default, 'string', $hint);
    }

    /**
     * A list of whole numbers.
     *
     * Name the arguments when declaring one - `ofInts(default: [...], hint: ...)` - so the
     * declaration says what each value is for and survives the parameters being changed.
     *
     * @param array<int, mixed> $default The list shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     * @return self
     */
    public static function ofInts(array $default, ?string $hint = null): self
    {
        return new self($default, 'int', $hint);
    }

    /**
     * A list of decimal numbers.
     *
     * Name the arguments when declaring one - `ofFloats(default: [...], hint: ...)` - so the
     * declaration says what each value is for and survives the parameters being changed.
     *
     * @param array<int, mixed> $default The list shipped when nothing is stored.
     * @param string|null $hint What to tell the operator about the field.
     * @return self
     */
    public static function ofFloats(array $default, ?string $hint = null): self
    {
        return new self($default, 'float', $hint);
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
        return SettingWidget::Json;
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
        return ['rows' => 6];
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

        if (!is_array($value)) {
            return '';
        }

        return (string)json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * @inheritDoc
     */
    public function normalize(mixed $value): mixed
    {
        // a caller reaching the service directly hands over the list itself
        if (is_array($value)) {
            return $this->castList($value);
        }

        if (!is_string($value)) {
            throw new SettingValueException(__d('settings', 'A list was expected.'));
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SettingValueException(
                __d('settings', 'The list is not valid JSON: {reason}', ['reason' => $exception->getMessage()]),
                null,
                $exception,
            );
        }

        if (!is_array($decoded)) {
            throw new SettingValueException(__d('settings', 'A list was expected, not a single value.'));
        }

        return $this->castList($decoded);
    }

    /**
     * Check a whole list and return it with its items cast to the declared type.
     *
     * @param array<mixed> $items The items to check.
     * @return array<int, float|int|string>
     * @throws \Settings\Exception\SettingValueException When an item is of another type.
     */
    private function castList(array $items): array
    {
        if (!array_is_list($items)) {
            throw new SettingValueException(
                __d('settings', 'A list of items was expected, but the value is keyed.'),
            );
        }

        $cast = [];

        foreach ($items as $position => $item) {
            $value = $this->cast($item);

            if ($value === null) {
                throw new SettingValueException(__d(
                    'settings',
                    'Item {position} of the list is not of type {type}.',
                    ['position' => $position + 1, 'type' => $this->itemType],
                ));
            }

            $cast[] = $value;
        }

        return $cast;
    }

    /**
     * Cast a single item to the declared type, or say it does not fit.
     *
     * Numbers written as text are taken as numbers; anything that would lose its value is refused.
     *
     * @param mixed $item The item to cast.
     * @return string|float|int|null The cast item, or null when it does not fit.
     */
    private function cast(mixed $item): float|int|string|null
    {
        if ($this->itemType === 'string') {
            return is_string($item) || is_int($item) || is_float($item) ? (string)$item : null;
        }

        if (!is_int($item) && !is_float($item) && !(is_string($item) && is_numeric($item))) {
            return null;
        }

        if ($this->itemType === 'float') {
            return (float)$item;
        }

        $number = (float)$item;

        return $number === floor($number) ? (int)$number : null;
    }
}
