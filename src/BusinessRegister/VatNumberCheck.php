<?php
declare(strict_types=1);

namespace App\BusinessRegister;

/**
 * What a register answered about a VAT number.
 *
 * The name comes along with the status because it is what makes the answer worth reading: a
 * number that checks out but belongs to somebody else is a mistake the status alone would not
 * show. A register that confirms nothing has no name to give either.
 */
final readonly class VatNumberCheck
{
    /**
     * @param \App\BusinessRegister\VatNumberStatus $status What the register says the number is.
     * @param string|null $company Who holds it, as the register writes the name.
     */
    public function __construct(
        public VatNumberStatus $status,
        public ?string $company = null,
    ) {
    }
}
