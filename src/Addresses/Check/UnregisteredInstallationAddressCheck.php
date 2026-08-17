<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Live installation addresses with no reference into the address registry, all of them.
 *
 * The strict half, and the working list for the reports to the regulator: this is exactly
 * what those reports leave out. Most of these are deliberate - somewhere with no house
 * number to look up, placed by hand instead - so it is not a list of faults and the
 * dashboard leaves it alone. Whoever is preparing a report wants to see them anyway.
 */
class UnregisteredInstallationAddressCheck extends AbstractRegistryReferenceCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unregistered_installation_address';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Installation Address Not in the Address Registry');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every running service sits at an address known to the registry.');
    }

    /**
     * This one is not a list of faults, so it stays off the dashboard.
     *
     * @return bool
     */
    #[Override]
    public function onDashboard(): bool
    {
        return false;
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        return $this->unregistered();
    }
}
