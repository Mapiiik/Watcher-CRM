<?php
declare(strict_types=1);

namespace Radius\Check;

use Cake\I18n\Date;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radusergroup;

/**
 * An active RADIUS account that does not agree with its contract, and what the contract says.
 */
final class AccountFinding
{
    /**
     * @param \Radius\Model\Entity\Account $account The account, with its groups.
     * @param string|null $expected_groupname The group the tariff names, or null where it names none.
     * @param \Cake\I18n\Date|null $tariff_from Where the tariff names none yet, the day a later one starts.
     * @param array<string, \App\Model\Entity\Contract|null> $stray_addresses The addresses the account
     *   holds that its contract does not, each with the contract that has it now, if any does.
     * @param list<string> $competing_addresses The addresses the account is handed where it takes
     *   only one of them.
     */
    public function __construct(
        public readonly Account $account,
        public readonly ?string $expected_groupname = null,
        public readonly ?Date $tariff_from = null,
        public readonly array $stray_addresses = [],
        public readonly array $competing_addresses = [],
    ) {
    }

    /**
     * The groups the account is in.
     *
     * @return list<string>
     */
    public function groupnames(): array
    {
        return array_values(array_map(
            fn(Radusergroup $radusergroup): string => $radusergroup->groupname,
            $this->account->radusergroup,
        ));
    }
}
