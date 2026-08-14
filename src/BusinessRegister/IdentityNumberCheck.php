<?php
declare(strict_types=1);

namespace App\BusinessRegister;

/**
 * What a register answered about an identification number.
 *
 * The check digit says the number is well formed, which is not the same as it being the right
 * company's - only the register can say that, by naming who holds it.
 */
final readonly class IdentityNumberCheck
{
    /**
     * @param \App\BusinessRegister\IdentityNumberStatus $status Whether any register holds it.
     * @param string|null $company Who holds it, as the register writes the name.
     * @param list<array{key: string, label: string, seat: bool}> $addresses Where the holder is
     *      registered - its seat and wherever else it does business - each as the national
     *      address registry knows it, in the "source|reference" form an address form is filled in
     *      from. Empty where the register hands over no such reference, a seat abroad included.
     */
    public function __construct(
        public IdentityNumberStatus $status,
        public ?string $company = null,
        public array $addresses = [],
    ) {
    }

    /**
     * The answer in a few words: who holds the number, or that nobody does.
     *
     * Naming the holder already says the register found it, so the status is only spelled out
     * where there is no name to give.
     *
     * @return string
     */
    public function note(): string
    {
        return $this->company ?? $this->status->label();
    }
}
