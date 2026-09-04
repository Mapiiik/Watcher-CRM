<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use Cake\I18n\Date;
use InvalidArgumentException;

/**
 * A set of dates a proposal asks an existing record for.
 *
 * A field that is not named is left alone; a field named with no date is cleared. Those are two
 * different things - "the obligation stays as it is" and "there is no obligation any more" - so an
 * absent key and a null one cannot be collapsed into one.
 *
 * Subclasses say which fields they will answer for, and nothing else gets in.
 */
abstract class ProposedDates
{
    /**
     * The fields a proposal may ask this record for.
     *
     * @var array<string>
     */
    protected const FIELDS = [];

    /**
     * @param array<string, \Cake\I18n\Date|null> $fields Only the fields the proposal names.
     */
    final private function __construct(private readonly array $fields)
    {
    }

    /**
     * A proposal that asks this record for nothing.
     *
     * @return static
     */
    public static function untouched(): static
    {
        return new static([]);
    }

    /**
     * Whether the proposal says anything about this record at all.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->fields === [];
    }

    /**
     * Whether the proposal names the given field.
     *
     * @param string $field Which field.
     * @return bool
     */
    public function names(string $field): bool
    {
        return array_key_exists($field, $this->fields);
    }

    /**
     * Whether the proposal names the given field and puts a date on it.
     *
     * @param string $field Which field.
     * @return bool
     */
    public function sets(string $field): bool
    {
        return $this->names($field) && $this->fields[$field] !== null;
    }

    /**
     * What the proposal asks the given field to become; null clears it.
     *
     * @param string $field Which field.
     * @return \Cake\I18n\Date|null
     * @throws \InvalidArgumentException When the proposal does not name the field.
     */
    public function get(string $field): ?Date
    {
        if (!$this->names($field)) {
            throw new InvalidArgumentException(sprintf('The proposal says nothing about %s.', $field));
        }

        return $this->fields[$field];
    }

    /**
     * Reads the fields back from the stored shape.
     *
     * @param array<string, mixed> $stored The stored fields.
     * @return static
     * @throws \InvalidArgumentException When a field is not one a proposal may ask for.
     */
    public static function fromArray(array $stored): static
    {
        $fields = [];

        foreach ($stored as $field => $value) {
            if (!in_array($field, static::FIELDS, true)) {
                throw new InvalidArgumentException(
                    sprintf('A proposal cannot ask for %s here.', (string)$field),
                );
            }

            $fields[(string)$field] = $value === null || $value === ''
                ? null
                : new Date((string)$value);
        }

        return new static($fields);
    }

    /**
     * Writes the fields out in the shape they are stored in.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return array_map(
            fn(?Date $date): ?string => $date?->toDateString(),
            $this->fields,
        );
    }
}
