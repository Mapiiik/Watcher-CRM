<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Hr;

/**
 * One row of a HAKOM form, the way it is copied into e-Operator: its code, its words, its value.
 */
final class HakomRow
{
    /**
     * @param string $code The row's number in the form's tree.
     * @param string $label The form's words for it.
     * @param float|int $value What goes in.
     * @param string $unit The form's unit, kom, TB or EUR.
     */
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly int|float $value,
        public readonly string $unit,
    ) {
    }
}
