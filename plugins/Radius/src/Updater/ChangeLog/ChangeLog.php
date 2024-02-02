<?php
declare(strict_types=1);

namespace Radius\Updater\ChangeLog;

use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
use Radius\Model\Entity\Account;

/**
 * RADIUS Updater Changelog
 */
class ChangeLog
{
    use LocatorAwareTrait;

    /**
     * @var array<string, \Radius\Updater\ChangeLog\Change>
     */
    private array $changes = [];

    /**
     * Add Change
     */
    public function addChange(Change $change): void
    {
        $this->changes[$change->getAccount()->username] = $change;
    }

    /**
     * Get Change
     */
    public function getChange(string $username): Change
    {
        return $this->changes[$username];
    }

    /**
     * Has Change
     */
    public function hasChange(string $username): bool
    {
        return isset($this->changes[$username]);
    }

    /**
     * Get Changes
     *
     * @return array<\Radius\Updater\ChangeLog\Change>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * Add change for related data
     *
     * @throws \InvalidArgumentException When unsupported related data.
     */
    public function addChangeForRelatedData(
        Account $account,
        string $relatedData,
        array $original,
        array $changed
    ): void {
        if (!$this->hasChange($account->username)) {
            /** @var \App\Model\Entity\Customer $customer */
            $customer = $this->fetchTable('Customers')->get($account->customer_id);
            /** @var \App\Model\Entity\Contract $contract */
            $contract = $this->fetchTable('Contracts')->get($account->contract_id);

            $change = new Change($account, $customer, $contract);
            $this->addChange($change);
        }

        $change = $this->getChange($account->username);

        switch ($relatedData) {
            case 'radcheck':
                $change->setRadcheckChange(new RadcheckChange($original, $changed));
                break;
            case 'radreply':
                $change->setRadreplyChange(new RadreplyChange($original, $changed));
                break;
            case 'radusergroup':
                $change->setRadusergroupChange(new RadusergroupChange($original, $changed));
                break;
            default:
                throw new InvalidArgumentException("Invalid related data: $relatedData");
        }
    }
}
