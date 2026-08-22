<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\Http\Answer;

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
     * A number the register cannot be asked about at all is answered with nothing rather than
     * refused: the next register may know it.
     *
     * @param string $vatNumber The number as it is stored, prefix included.
     * @return \App\Http\Answer<\App\BusinessRegister\VatNumberCheck|null>
     */
    public function vatNumberCheck(string $vatNumber): Answer;
}
