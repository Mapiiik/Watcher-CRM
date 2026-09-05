<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * What a proposal is being drawn up for.
 *
 * The papers a proposal produces used to carry this by themselves: whoever picked the document said
 * what was meant by picking it. Once printing moved to the proposal, the intent had nowhere to live,
 * and what the proposal holds does not say it - an end date on a version reads the same whether the
 * contract runs for a fixed term or is being brought to an end, and those two want opposite things
 * of the obligation.
 *
 * So it is asked once, at the top of the form. It decides what the form goes on to ask, which rules
 * the proposal is held to, and which document is offered first.
 */
enum ProposalPurpose: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case NewContract = 'new-contract';
    case ServiceChange = 'service-change';
    case Termination = 'termination';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::NewContract => __('A new contract'),
            self::ServiceChange => __('A change of what is provided'),
            self::Termination => __('Bringing it to an end'),
        };
    }

    /**
     * Whether papers for this purpose take effect on a day of their own.
     *
     * A new contract starts with its version. A change is agreed while the version runs, so it says
     * its own day. An end says the day it ends on, and the day it takes effect follows from that.
     *
     * @return bool
     */
    public function asksForItsOwnDay(): bool
    {
        return $this === self::ServiceChange;
    }

    /**
     * Whether a proposal for this purpose brings the version it belongs to to an end.
     *
     * @return bool
     */
    public function ends(): bool
    {
        return $this === self::Termination;
    }

    /**
     * The document to offer first, before the operator has chosen one.
     *
     * Only a suggestion: which documents may be printed at all is worked out from the proposal, so
     * that a type added later still prints from what is already on file.
     *
     * @param bool $replaces Whether the proposal terminates an earlier version of the same contract.
     * @return \App\Model\Enum\ContractPrintType
     */
    public function suggests(bool $replaces): ContractPrintType
    {
        return match ($this) {
            self::NewContract => $replaces
                ? ContractPrintType::ContractNewX
                : ContractPrintType::ContractNew,
            self::ServiceChange => ContractPrintType::ContractAmendment,
            self::Termination => ContractPrintType::ContractTermination,
        };
    }
}
