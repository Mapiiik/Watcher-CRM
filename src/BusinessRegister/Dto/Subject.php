<?php
declare(strict_types=1);

namespace App\BusinessRegister\Dto;

/**
 * One entry as a business register holds it.
 *
 * Every register answers in this shape whatever it asks upstream, so nothing above them has to
 * know which register an entry came from.
 *
 * A sole trader trades under their own name, so a register writes a person where it otherwise
 * writes a company. Such an entry fills the name parts and leaves {@see $company} empty, which is
 * how the CRM tells a person trading from a legal entity. {@see $name} is what the register calls
 * the entry either way, and is what a suggestion list and a customer's detail show.
 *
 * The address is only ever a label. What is stored comes from {@see $addresses}, which name places
 * in the national address registry rather than describing them.
 */
final readonly class Subject
{
    /**
     * @param string $reference What the register is asked with to get this entry again.
     * @param string|null $name What the register calls the entry.
     * @param string|null $company The company, empty for a sole trader.
     * @param string|null $title What goes before a person's name.
     * @param string|null $firstName A person's given name.
     * @param string|null $lastName A person's family name.
     * @param string|null $suffix What goes after a person's name.
     * @param string|null $dateOfBirth When a person was born, never a company.
     * @param list<\App\BusinessRegister\Dto\Officer> $officers Who sits in the statutory body.
     * @param string|null $identityNumber The number the subject is registered under.
     * @param string|null $vatNumber The VAT number, where the register holds one.
     * @param string|null $address The seat on one line, as a label and nothing more.
     * @param list<\App\BusinessRegister\Dto\RegisteredAddress> $addresses Where it is registered.
     * @param array<string, mixed> $raw The entry in the shape the registers share.
     */
    public function __construct(
        public string $reference,
        public ?string $name = null,
        public ?string $company = null,
        public ?string $title = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $suffix = null,
        public ?string $dateOfBirth = null,
        public array $officers = [],
        public ?string $identityNumber = null,
        public ?string $vatNumber = null,
        public ?string $address = null,
        public array $addresses = [],
        public array $raw = [],
    ) {
    }

    /**
     * The same entry, with the named person as who the company is represented by.
     *
     * Which of several people that is stays the operator's to choose, so a key naming nobody here
     * is one left over from a company picked before this one, and what the entry says of itself
     * stands instead.
     *
     * @param string|null $officerKey The person to take the name from, of those the register named.
     * @return self
     */
    public function representedBy(?string $officerKey): self
    {
        $officer = $this->officer($officerKey);

        if ($officer === null) {
            return $this;
        }

        return new self(
            reference: $this->reference,
            name: $this->name,
            company: $this->company,
            title: $officer->title,
            firstName: $officer->firstName,
            lastName: $officer->lastName,
            suffix: $officer->suffix,
            dateOfBirth: $officer->dateOfBirth,
            officers: $this->officers,
            identityNumber: $this->identityNumber,
            vatNumber: $this->vatNumber,
            address: $this->address,
            addresses: $this->addresses,
            raw: $this->raw,
        );
    }

    /**
     * One of the people sitting, by the key naming them.
     *
     * @param string|null $officerKey The person to find.
     * @return \App\BusinessRegister\Dto\Officer|null
     */
    public function officer(?string $officerKey): ?Officer
    {
        if ($officerKey === null) {
            return null;
        }

        foreach ($this->officers as $officer) {
            if ($officer->key === $officerKey) {
                return $officer;
            }
        }

        return null;
    }
}
