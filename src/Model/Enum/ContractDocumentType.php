<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * ContractDocumentType Enum
 */
enum ContractDocumentType: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case ContractNew = 'contract-new';
    case ContractNewX = 'contract-new-x';
    case ContractAmendment = 'contract-amendment';
    case ContractTermination = 'contract-termination';
    case ContractSummary = 'contract-summary';
    case HandoverInstallation = 'handover-protocol-installation';
    case HandoverUninstallation = 'handover-protocol-uninstallation';

    // Papers of an ending that come from the other side. Nobody draws them here - one is written
    // by the customer and the other by an office - but they belong to the papers of the ending as
    // much as anything we drew, so they are filed with them.
    case TerminationNotice = 'termination-notice';
    case DeathCertificate = 'death-certificate';

    // And whatever else came with the papers: a power of attorney, the owner of the building
    // agreeing to the cabling, anything the operator was handed and has nowhere else to keep.
    // One case rather than a list of them, because the list has no end - what each paper is, its
    // own file name says.
    case Other = 'other';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::ContractNew =>
                __('Contract for the provision of services'),

            self::ContractNewX =>
                __('Contract for the provision of services (with termination of the original contract)'),

            self::ContractAmendment =>
                __('Amendment to the contract for the provision of services'),

            self::ContractTermination =>
                __('Agreement to terminate contract for the provision of services'),

            self::ContractSummary =>
                __('Contract summary'),

            self::HandoverInstallation =>
                __('Handover protocol - Installation of internet connection'),

            self::HandoverUninstallation =>
                __('Handover protocol - Internet connection uninstallation'),

            self::TerminationNotice =>
                __('Notice of termination from the customer'),

            self::DeathCertificate =>
                __('Death certificate'),

            self::Other =>
                __('Other document'),
        };
    }

    /**
     * Whether this is a paper the application generates.
     *
     * Most are: somebody asks for one and it is written here. Three are not - a notice of
     * termination is the customer's own letter, a death certificate comes from an office, and
     * whatever else was handed over came from wherever it came from - so they are only ever
     * filed, never owed and never generated.
     *
     * @return bool
     */
    public function canBeGenerated(): bool
    {
        return match ($this) {
            self::TerminationNotice, self::DeathCertificate, self::Other => false,
            default => true,
        };
    }

    /**
     * Whether this is the agreement itself, the paper the customer's signature is about.
     *
     * A signed copy of one of these is what a recorded signature waits for. The summary and the
     * handover protocols go with the agreement and may be signed too, but they do not stand in for
     * it. Named one by one, so that a new type is asked on its own.
     *
     * @return bool
     */
    public function isTheAgreement(): bool
    {
        return match ($this) {
            self::ContractNew, self::ContractNewX, self::ContractAmendment, self::ContractTermination => true,
            default => false,
        };
    }

    /**
     * Whether having the paper on file is having the other side's answer.
     *
     * The customer's own letter says what they want without being signed on a paper of ours, and a
     * certificate from an office needs nobody's signature at all. Filed as they came back, they
     * settle a signature the way a signed copy of ours would.
     *
     * Named one by one rather than read off whether the paper is generated: a paper that only
     * comes back is not by that alone somebody's answer, and each new type is asked on its own.
     *
     * @return bool
     */
    public function speaksForItself(): bool
    {
        return match ($this) {
            self::TerminationNotice, self::DeathCertificate => true,
            default => false,
        };
    }

    /**
     * Indicates whether this document type requires selecting
     * a contract version to be executed.
     *
     * This corresponds to validation of:
     *  - ContractPrintData::$contractVersionToBeExecuted
     *
     * @return bool
     */
    public function requiresContractVersionToBeExecuted(): bool
    {
        return in_array($this, [
            self::ContractNew,
            self::ContractNewX,
            self::ContractAmendment,
            self::ContractSummary,
            self::HandoverInstallation,
        ], true);
    }

    /**
     * Indicates whether this document type requires selecting
     * an existing contract version to be terminated.
     *
     * This corresponds to validation of:
     *  - ContractPrintData::$contractVersionToBeTerminated
     *  - contract_number_to_be_terminated
     *
     * @return bool
     */
    public function requiresContractVersionToBeTerminated(): bool
    {
        return in_array($this, [
            self::ContractNewX,
            self::ContractTermination,
            self::HandoverUninstallation,
        ], true);
    }

    /**
     * Indicates whether this document type requires
     * an effective date of amendment.
     *
     * This corresponds to validation of:
     *  - effective_date_of_the_amendment
     *  - conclusion_date of the selected contract version
     *
     * @return bool
     */
    public function requiresEffectiveDateOfTheAmendment(): bool
    {
        return $this === self::ContractAmendment;
    }

    /**
     * Indicates whether this document type requires
     * the contract number to be terminated.
     *
     * This corresponds to validation of:
     *  - contract_number_to_be_terminated
     *
     * @return bool
     */
    public function requiresContractNumberToBeTerminated(): bool
    {
        return in_array($this, [
            self::ContractNewX,
            self::ContractTermination,
            self::HandoverUninstallation,
        ], true);
    }

    /**
     * Indicates whether this document type is printed from a proposal.
     *
     * Every one of them is, today. It is a property of the document rather than a rule of printing,
     * so that a type added later which binds nobody can say so for itself.
     *
     * @return bool
     */
    public function requiresProposal(): bool
    {
        return true;
    }

    /**
     * Indicates whether this document has anywhere for our signature to go.
     *
     * Everything that binds somebody does. The summary does not: it says what is on offer before
     * anybody is bound by it, so it carries no signature block at all.
     *
     * Only what the operator is offered. What actually happens is decided by the paper itself,
     * which carries the marks that say where it is signed - so a document added later that
     * forgets to answer here still cannot be signed by accident.
     *
     * @return bool
     */
    public function mayCarryOurSignature(): bool
    {
        // A copy carrying our signature is the paper we drew, stamped. Of a paper we never drew
        // there is no such copy to be had - the question is not asked of one today, but it is a
        // question with an answer, and a wrong one lying about is what gets picked up later.
        return $this->canBeGenerated() && $this !== self::ContractSummary;
    }

    /**
     * Indicates whether this document type represents
     * a handover protocol and therefore requires
     * technical connection details enrichment.
     *
     * This controls enrichment of:
     *  - access point
     *  - RADIUS username
     *  - RADIUS password
     *
     * @return bool
     */
    public function isHandoverProtocol(): bool
    {
        return in_array($this, [
            self::HandoverInstallation,
            self::HandoverUninstallation,
        ], true);
    }
}
