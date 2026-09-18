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
            self::NewContract => __('New contract'),
            self::ServiceChange => __('Change of services'),
            self::Termination => __('Contract termination'),
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
     * How far papers drawn up for this purpose travel before nobody waits for them any more.
     *
     * Everything a proposal of a contract asks for reaches the records by being applied, so
     * every purpose here goes the whole way. A purpose that only hands a paper over would stop
     * earlier, and the rest of the application would follow without being told twice.
     *
     * @return \App\Model\Enum\ProposalStep
     */
    public function lastStep(): ProposalStep
    {
        return match ($this) {
            self::NewContract, self::ServiceChange, self::Termination => ProposalStep::Applied,
        };
    }

    /**
     * Whether a proposal for this purpose may bring a version into being rather than name one.
     *
     * Only a new contract can: a change amends a version that was agreed to and an ending ends one
     * that is running, so neither has anything to start.
     *
     * @return bool
     */
    public function mayStartAVersion(): bool
    {
        return $this === self::NewContract;
    }

    /**
     * The document to offer first, before the operator has chosen one.
     *
     * Only a suggestion: which documents may be printed at all is worked out from the proposal, so
     * that a type added later still prints from what is already on file.
     *
     * @param bool $replaces Whether the proposal terminates an earlier version of the same contract.
     * @return \App\Model\Enum\ContractDocumentType
     */
    public function suggests(bool $replaces): ContractDocumentType
    {
        return match ($this) {
            self::NewContract => $replaces
                ? ContractDocumentType::ContractNewX
                : ContractDocumentType::ContractNew,
            self::ServiceChange => ContractDocumentType::ContractAmendment,
            self::Termination => ContractDocumentType::ContractTermination,
        };
    }
}
