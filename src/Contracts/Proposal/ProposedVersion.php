<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

/**
 * What a proposal asks the version it belongs to to become.
 */
final class ProposedVersion extends ProposedDates
{
    /**
     * The fields a proposal may ask a version for.
     *
     * @var array<string>
     */
    protected const FIELDS = [
        'valid_until',
        'obligation_until',
    ];

    /**
     * What to call one of these fields where somebody is reading rather than filling it in.
     *
     * @param string $field Which field.
     * @return string
     */
    public static function label(string $field): string
    {
        return match ($field) {
            'valid_until' => __('Valid Until'),
            'obligation_until' => __('Obligation Until'),
            default => $field,
        };
    }

    /**
     * Whether the proposal puts an end date on the version.
     *
     * Naming the field and clearing it is not putting an end on it, so both have to hold.
     *
     * @return bool
     */
    public function endsTheVersion(): bool
    {
        return $this->sets('valid_until');
    }
}
