<?php
declare(strict_types=1);

namespace Radius\Updater\ChangeLog;

use App\Model\Table\ContractsTable;
use App\Model\Table\CustomersTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radcheck;
use Radius\Model\Entity\Radreply;
use Radius\Model\Entity\Radusergroup;

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
     * @template T of \Radius\Model\Entity\Radcheck|\Radius\Model\Entity\Radreply|\Radius\Model\Entity\Radusergroup
     * @param array<T> $original
     * @param array<T> $changed
     * @throws \InvalidArgumentException When unsupported related data.
     */
    public function addChangeForRelatedData(
        Account $account,
        string $relatedData,
        array $original,
        array $changed,
    ): void {
        if (!$this->hasChange($account->username)) {
            $customer = $this->fetchTable(CustomersTable::class)->get($account->customer_id);
            $contract = $this->fetchTable(ContractsTable::class)->get($account->contract_id);

            $change = new Change($account, $customer, $contract);
            $this->addChange($change);
        }

        $change = $this->getChange($account->username);

        switch ($relatedData) {
            case 'radcheck':
                $change->setRadcheckChange(
                    new RadcheckChange(
                        $this->assertRadcheckArray($original),
                        $this->assertRadcheckArray($changed),
                    ),
                );
                break;

            case 'radreply':
                $change->setRadreplyChange(
                    new RadreplyChange(
                        $this->assertRadreplyArray($original),
                        $this->assertRadreplyArray($changed),
                    ),
                );
                break;

            case 'radusergroup':
                $change->setRadusergroupChange(
                    new RadusergroupChange(
                        $this->assertRadusergroupArray($original),
                        $this->assertRadusergroupArray($changed),
                    ),
                );
                break;

            default:
                throw new InvalidArgumentException("Invalid related data: $relatedData");
        }
    }

    /**
     * @param array<mixed> $data
     * @return array<\Radius\Model\Entity\Radcheck>
     */
    private function assertRadcheckArray(array $data): array
    {
        foreach ($data as $item) {
            if (!$item instanceof Radcheck) {
                throw new InvalidArgumentException('Expected Radcheck[]');
            }
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     * @return array<\Radius\Model\Entity\Radreply>
     */
    private function assertRadreplyArray(array $data): array
    {
        foreach ($data as $item) {
            if (!$item instanceof Radreply) {
                throw new InvalidArgumentException('Expected Radreply[]');
            }
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     * @return array<\Radius\Model\Entity\Radusergroup>
     */
    private function assertRadusergroupArray(array $data): array
    {
        foreach ($data as $item) {
            if (!$item instanceof Radusergroup) {
                throw new InvalidArgumentException('Expected Radusergroup[]');
            }
        }

        return $data;
    }
}
