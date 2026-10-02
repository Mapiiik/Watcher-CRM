<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Contracts\Unsigned\UnsignedWaits;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A service that is running and charged for with no contract version covering it.
 *
 * The ordinary way this happens is the ordinary way the work is done: the operator puts the
 * billings in so that the line runs the day it is installed, and leaves the version and all the
 * paperwork to a proposal. Until somebody applies that proposal there is no version at all, and
 * everything that watches unsigned paperwork used to be watching versions - so exactly the newest
 * work was the work nobody was watching.
 *
 * The other way is a contract whose last version ran out while the service kept running, which no
 * other check reports either: the gap between versions is only looked for where a later one exists.
 *
 * Like its neighbour about versions, this answers two different questions:
 *
 *   Keeping to what is running, the answer is the day's work - what the automation chases and blocks
 *   by, from the day the office watches from.
 *
 *   Lifting it, the answer is every running service with no version on file, whatever its dates.
 *   That is putting the history straight, and on this installation it is well over a thousand
 *   contracts an import left behind.
 */
class ServiceWithoutContractVersionCheck extends AbstractContractCheck
{
    /**
     * @param \App\Contracts\Unsigned\UnsignedPaperwork $paperwork What counts as unsigned, shared
     *   with the command that chases it and the run that blocks on it.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     */
    public function __construct(
        private UnsignedPaperwork $paperwork,
        CheckScope $scope = new CheckScope(),
    ) {
        parent::__construct($scope);
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function contractField(): ?string
    {
        return 'Contracts.id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'service_without_contract_version';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Running Service Without a Contract Version');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every service being charged for has a contract version behind it.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->scope->ignore_inactive
            ? $this->paperwork->findServicesDue(UnsignedWaits::none(), Date::today())
            : $this->paperwork->findEveryServiceWithoutAVersion();

        return $this->scoped($this->paperwork->withServiceDeadlines(
            $query,
            Date::today(),
            UnsignedWaits::beforeNotifying(),
            UnsignedWaits::beforeBlocking(),
        ));
    }
}
