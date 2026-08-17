<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Live installation addresses nobody knows the position of.
 *
 * The tolerant half of the registry checks, and the one the dashboard carries. It passes
 * over addresses whose coordinates were set by hand: a mast on a field or a box on a roof
 * has no house number to look up, and somebody has already said where it is. What is left
 * is neither in the registry nor placed by anyone - the service runs somewhere we cannot
 * point at.
 *
 * On the restored dump this is a handful of addresses, against a hundred for
 * {@see \App\Addresses\Check\UnregisteredInstallationAddressCheck}. Almost every address
 * without a reference has coordinates set by hand, so the strict count would be noise here
 * and nobody would read the card.
 */
class UnlocatedInstallationAddressCheck extends AbstractRegistryReferenceCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unlocated_installation_address';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Installation Address of Unknown Position');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every running service sits at an address we can point at.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        // The column is nullable and an unset flag means nobody placed it either, which
        // `IS NOT TRUE` says in one go - `!= true` would drop the rows that are null.
        return $this->unregistered()
            ->where(['Addresses.manual_coordinate_setting IS NOT TRUE']);
    }
}
