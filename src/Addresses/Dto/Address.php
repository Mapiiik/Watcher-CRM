<?php
declare(strict_types=1);

namespace App\Addresses\Dto;

/**
 * One address as a national registry holds it.
 *
 * Two countries answer through the same API and their registries number their addresses their own
 * way - RÚIAN by `kod_adm`, the Croatian one by `ogc_fid` - so an address is only ever named by the
 * pair of the registry it came from and its number there. {@see self::key()} is that pair written
 * the way this application stores it.
 *
 * Carries what is read of an address here and the answer as it arrived beside it. Distance and
 * score are the two fields that belong to a question rather than to the address: the first comes of
 * asking what is near a point, the second of asking what a typed line might have meant.
 */
final readonly class Address
{
    /**
     * @param string $source Which registry the address came from.
     * @param string $registryReference The number that registry keeps it under.
     * @param string|null $formattedAddress The address on one line, as the registry writes it.
     * @param string|null $street The street, where the place has streets.
     * @param string|null $houseNumber The number on the house.
     * @param string|null $city The town or village.
     * @param string|null $postalCode The postal code.
     * @param string|null $numberType Whether the number is a house or a registration number.
     * @param float|null $latitude Where the address is.
     * @param float|null $longitude Where the address is.
     * @param float|null $distance How far it lies from the point that was asked about, in metres.
     * @param float|null $score How well it matches what was typed, between nothing and one.
     * @param array<string, mixed> $raw The address as it arrived.
     */
    public function __construct(
        public string $source,
        public string $registryReference,
        public ?string $formattedAddress = null,
        public ?string $street = null,
        public ?string $houseNumber = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $numberType = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $distance = null,
        public ?float $score = null,
        public array $raw = [],
    ) {
    }

    /**
     * The registry and the number in it, which is how this application stores the reference.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->source . '|' . $this->registryReference;
    }

    /**
     * Whether the number on the house is a registration number rather than a house number.
     *
     * Czech addresses come either way and the two are written differently, so which one it is
     * travels with the number.
     *
     * @return bool
     */
    public function hasRegistrationNumber(): bool
    {
        return $this->numberType === 'registration';
    }
}
