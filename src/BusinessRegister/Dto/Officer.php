<?php
declare(strict_types=1);

namespace App\BusinessRegister\Dto;

/**
 * One person sitting in a company's statutory body.
 *
 * A contract names who the company was represented by, so what is kept here is a person's name in
 * the parts the CRM stores it in. The key names them among the others sitting - a register numbers
 * its people its own way, or not at all, so it is made of what distinguishes them rather than
 * taken from the register.
 */
final readonly class Officer
{
    /**
     * @param string $key What names this person among the others sitting.
     * @param string|null $title What goes before the name.
     * @param string|null $firstName The given name.
     * @param string|null $lastName The family name.
     * @param string|null $suffix What goes after the name.
     * @param string|null $dateOfBirth When they were born, where the register says.
     */
    public function __construct(
        public string $key,
        public ?string $title = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $suffix = null,
        public ?string $dateOfBirth = null,
    ) {
    }
}
