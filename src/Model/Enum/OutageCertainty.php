<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * OutageCertainty Enum
 *
 * How much a planned outage the network management system reports is worth taking at its word.
 * Only an outage the distributor named against the supply point itself is about that mast and
 * nothing else; one found by looking at the addresses around a mast is a good guess, and is said
 * to be one - the power reaching a mast need not come from the house nearest to it.
 *
 * Nothing here decides this: the other application does, and writes it down beside the outage.
 * Kept as a kind of its own all the same, so the two words a card turns on cannot be spelled two
 * ways by two callers. A word neither of them knows reads as nothing rather than as a certainty.
 *
 * @see \App\NMS\Dto\PowerOutage
 */
enum OutageCertainty: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case Certain = 'certain';
    case Probable = 'probable';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Certain => __('Certain'),
            self::Probable => __('Probable'),
        };
    }
}
