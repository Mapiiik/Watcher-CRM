<?php
declare(strict_types=1);

namespace Radius\Updater\ChangeLog;

use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use Radius\Model\Entity\Account;

/**
 * RADIUS Updater Change
 */
class Change
{
    private readonly Account $account;

    private readonly Customer $customer;

    private readonly Contract $contract;

    private ?RadcheckChange $radcheck = null;

    private ?RadreplyChange $radreply = null;

    private ?RadusergroupChange $radusergroup = null;

    /**
     * Constructor
     */
    public function __construct(Account $account, Customer $customer, Contract $contract)
    {
        $this->account = $account;
        $this->customer = $customer;
        $this->contract = $contract;
    }

    /**
     * Set RadcheckChange
     */
    public function setRadcheckChange(RadcheckChange $radcheck): void
    {
        $this->radcheck = $radcheck;
    }

    /**
     * Set RadreplyChange
     */
    public function setRadreplyChange(RadreplyChange $radreply): void
    {
        $this->radreply = $radreply;
    }

    /**
     * Set RadusergroupChange
     */
    public function setRadusergroupChange(RadusergroupChange $radusergroup): void
    {
        $this->radusergroup = $radusergroup;
    }

    /**
     * Get RadcheckChange
     */
    public function getRadcheckChange(): ?RadcheckChange
    {
        return $this->radcheck;
    }

    /**
     * Get RadreplyChange
     */
    public function getRadreplyChange(): ?RadreplyChange
    {
        return $this->radreply;
    }

    /**
     * Get RadusergroupChange
     */
    public function getRadusergroupChange(): ?RadusergroupChange
    {
        return $this->radusergroup;
    }

    /**
     * Get Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * Get Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * Get Contract
     */
    public function getContract(): Contract
    {
        return $this->contract;
    }
}
