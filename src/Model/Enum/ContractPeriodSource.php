<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * Which pair of dates a contract's life is read from.
 *
 * The versions are what was actually agreed, and they are the better answer wherever they
 * exist. They do not always exist: about a sixth of the file carries none at all, and not
 * only from the old import - a fair share of what is written every year still never gets a
 * version, and most of those contracts are in a state that says they are running. A report
 * that only ever reads the versions is quietly answering about five sixths of the file.
 *
 * So the readings are all offered and none is hidden. Which one a question wants is a matter
 * of what the question is, and that is not ours to decide here: the paperwork, the service,
 * and the kit on the customer's wall stop on three different days, and a month looks
 * different depending on which of them is being counted.
 *
 * The fragments below name the `Contracts` alias outright, so they only work on a query
 * rooted at the contracts - not on one that reached them through a `contain`.
 */
enum ContractPeriodSource: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    /**
     * The versions: the earliest one begins the contract, the last one ends it.
     */
    case Versions = 'versions';

    /**
     * How long the service ran: from the day it went in to the day it was stopped.
     *
     * Worth knowing the cost of: `installation_date` is the day the service went in rather
     * than the day the contract began, and a contract without one is left out of the
     * beginnings entirely. That hole is already reported by
     * {@see \App\Contracts\Check\MissingInstallationDateCheck}.
     */
    case ServiceDates = 'service-dates';

    /**
     * How long the kit was on site: from the day it went in to the day it was fetched back.
     *
     * A different question from the one above and not a worse answer to it. A service is
     * routinely stopped long before anybody drives out to collect the equipment - on file
     * 671 contracts carry both dates and disagree about them - and which of the two a month
     * is being asked about depends on who is asking. The technician's month ends when the
     * kit comes back; the invoice stopped earlier.
     *
     * The sparser of the two, though: 785 contracts have an uninstallation date where 1699
     * have a termination date, because kit that was never collected never got one.
     */
    case EquipmentDates = 'equipment-dates';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Versions => __('From the contract versions'),
            self::ServiceDates => __('From the installation and termination dates'),
            self::EquipmentDates => __('From the installation and uninstallation dates'),
        };
    }

    /**
     * The one the query string names, or the versions where it names nothing that means
     * anything.
     *
     * Takes whatever arrived rather than a string: `?source[]=versions` hands back an array,
     * and a radio group whose companion input is empty hands back `''`. Neither is a source,
     * and neither should be a fatal error either.
     *
     * @param mixed $value Whatever the query string held under the name.
     * @return self
     */
    public static function fromQuery(mixed $value): self
    {
        return is_string($value) ? self::tryFrom($value) ?? self::Versions : self::Versions;
    }

    /**
     * The day the contract's life begins, as SQL over the contract.
     *
     * @return string
     */
    public function startsOnSql(): string
    {
        return match ($this) {
            self::Versions => self::FIRST_VERSION_FROM,
            // The same column for both, there being only one day on record for a contract
            // beginning. What the two differ about is where it stops.
            self::ServiceDates, self::EquipmentDates => 'Contracts.installation_date',
        };
    }

    /**
     * The day the contract's life ends, as SQL over the contract.
     *
     * A NULL is what carries "it has not ended" into the query, and the caller has to leave
     * it NULL rather than reach past it - that is the whole of what keeps a contract that is
     * still running out of a list of endings.
     *
     * @return string
     */
    public function endsOnSql(): string
    {
        return match ($this) {
            self::Versions => self::LAST_VERSION_UNTIL,
            self::ServiceDates => 'Contracts.termination_date',
            self::EquipmentDates => 'Contracts.uninstallation_date',
        };
    }

    /**
     * When the earliest version begins.
     *
     * An aggregate over all of them, unlike the end below, and deliberately so: a contract
     * that lapsed years ago and was signed again last month began when it first began. That
     * is what a list of new contracts is asking about.
     */
    private const FIRST_VERSION_FROM = '(
        SELECT MIN(FirstVersion.valid_from)
        FROM contract_versions FirstVersion
        WHERE FirstVersion.contract_id = Contracts.id
    )';

    /**
     * When the last version ends - the last one by the day it begins, not the latest end.
     *
     * `MAX(valid_until)` is the obvious way to write this and it is wrong. Two thirds of the
     * file - 2666 contracts of 4241 - end on a version with no `valid_until` at all, because
     * it has not ended; `MAX` passes over that NULL and answers with the end of the version
     * before it. And versions are contiguous, each one closed on the day before the next
     * begins ({@see \App\Contracts\Proposal\ProposalProjection}), so what comes back is an
     * amendment being reported as a termination. Counted over July 2026 that is 34 endings
     * where there are 29.
     *
     * Two versions that begin on the same day are not supposed to happen, and today none do;
     * {@see \App\Contracts\Check\OverlappingContractVersionsCheck} exists because they
     * sometimes will. `valid_until DESC` sorts the open-ended one of the pair to the front,
     * Postgres putting NULLs first on a descending sort, so the reading that says the
     * contract is still running wins. The id is only there to make the plan deterministic;
     * on its own it would be a v4 UUID deciding by coin flip.
     */
    private const LAST_VERSION_UNTIL = '(
        SELECT LastVersion.valid_until
        FROM contract_versions LastVersion
        WHERE LastVersion.contract_id = Contracts.id
        ORDER BY LastVersion.valid_from DESC, LastVersion.valid_until DESC, LastVersion.id DESC
        LIMIT 1
    )';
}
