<?php
declare(strict_types=1);

namespace App\Contracts\Unsigned;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\Customer;
use Cake\I18n\Date;

/**
 * One running service with nothing signed behind it, however that came about.
 *
 * Two different records say the same thing about a customer. A contract version that has taken
 * effect and come back unsigned is one. A contract nobody has drawn a version for at all is the
 * other, and it is the common one now that the papers wait on a proposal: the operator puts the
 * billings in so that the line runs the day it is installed, and the version arrives when the
 * proposal is applied.
 *
 * Whoever acts on them does not care which of the two it is looking at - the letter says the same
 * thing and the router cuts the same line off - so they arrive as one kind of thing, saying which
 * contract, which customer, and since when the service has been running on nothing.
 */
final readonly class UnsignedService
{
    /**
     * @param \App\Model\Entity\Contract $contract The contract whose service it is.
     * @param \App\Model\Entity\ContractVersion|null $version The version that came back unsigned,
     *   where there is one at all.
     * @param \Cake\I18n\Date $running_since The day what is running took effect - a version's start,
     *   or the day we began to charge for a service no version covers.
     */
    private function __construct(
        public Contract $contract,
        public ?ContractVersion $version,
        public Date $running_since,
    ) {
    }

    /**
     * A version that has taken effect and come back unsigned.
     *
     * @param \App\Model\Entity\ContractVersion $version The version.
     * @return self
     */
    public static function ofVersion(ContractVersion $version): self
    {
        /** @var \App\Model\Entity\Contract $contract */
        $contract = $version->contract;

        return new self($contract, $version, $version->valid_from ?? Date::today());
    }

    /**
     * A service that is running and charged for with no version covering it.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @param \Cake\I18n\Date $since The day we began to charge for it.
     * @return self
     */
    public static function ofContract(Contract $contract, Date $since): self
    {
        return new self($contract, null, $since);
    }

    /**
     * What tells one of these from another.
     *
     * The command asks for several days at once and would otherwise write about the same service
     * twice; the two sources cannot collide, because a contract with a version in force is never
     * the second kind.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->version !== null
            ? 'version:' . $this->version->id
            : 'contract:' . $this->contract->id;
    }

    /**
     * Who to write to about it.
     *
     * @return \App\Model\Entity\Customer|null
     */
    public function customer(): ?Customer
    {
        return $this->contract->customer ?? null;
    }
}
