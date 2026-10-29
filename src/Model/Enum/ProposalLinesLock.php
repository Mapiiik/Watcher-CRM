<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * Why the billing and equipment lines of a proposal that may still be edited cannot be changed.
 *
 * A paper drawn from a proposal is kept as it was drawn, so a line changed afterwards would leave
 * the paper saying one thing and the proposal another. The label is what the operator is told.
 */
enum ProposalLinesLock: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    /**
     * Documents generated from the proposal are on file. Discarding them unlocks the lines.
     */
    case PapersDrawn = 'papers-drawn';

    /**
     * The customer's signed copy of one of them is on file. Nothing unlocks the lines.
     */
    case SignedByTheCustomer = 'signed-by-the-customer';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::PapersDrawn => __(
                'Documents have already been generated from this proposal, so its lines stay as the'
                . ' documents have them. To change the lines, discard the documents and generate them'
                . ' again.',
            ),
            self::SignedByTheCustomer => __(
                'The customer has already signed a document from this proposal, so its lines stay as'
                . ' they agreed to them. To agree on something else, revoke this proposal and draw up a'
                . ' new one.',
            ),
        };
    }

    /**
     * Whether discarding the generated documents lifts the lock.
     *
     * @return bool
     */
    public function mayBeLifted(): bool
    {
        return $this === self::PapersDrawn;
    }
}
