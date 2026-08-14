<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\BusinessRegister\VatNumberCheck;

/**
 * A register that can also say what a VAT number is and who holds it.
 *
 * Not every register can, and not every register can say as much: a national register knows both
 * whether the company exists and whether it pays VAT, while VIES only knows VAT registrations.
 * A register that cannot be asked about a given number says so, and the next one is tried.
 */
interface VatNumberCheckInterface
{
    /**
     * What the register says about the VAT number.
     *
     * Null says the number is not one this register can be asked about at all - it carries no
     * country, or a country the register does not cover - which is not the same as the register
     * saying the number is invalid.
     *
     * @param string $vatNumber The number as it is stored, prefix included.
     * @return \App\BusinessRegister\VatNumberCheck|null
     * @throws \RuntimeException When the register cannot be reached or refuses the request.
     */
    public function vatNumberCheck(string $vatNumber): ?VatNumberCheck;
}
