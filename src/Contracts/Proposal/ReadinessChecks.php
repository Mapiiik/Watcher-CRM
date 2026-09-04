<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Contract;
use App\Model\Entity\IpAddress;
use App\Model\Enum\IpAddressTypeOfUse;
use Cake\Collection\Collection;
use Cake\Database\Exception\MissingConnectionException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Radius\Model\Table\AccountsTable;

/**
 * What has to be in place before papers are drawn up, and what the operator has to say about it
 * when it is not.
 *
 * These used to be asked at every printing and the answers thrown away, so a paper reprinted a year
 * later asked again about equipment that had since been handed back. They belong to the moment the
 * proposal is drawn up - which is the same moment the papers used to be printed, because the
 * equipment, the addresses and the account are all put in place before anybody prints anything and
 * only then does the line get installed and signed for.
 *
 * Nothing here is a rule about the proposal. It is a question about the contract, and an
 * unanswered one blocks saving until the operator either puts the thing in place or says why it is
 * deliberately absent.
 */
final class ReadinessChecks
{
    use LocatorAwareTrait;

    /**
     * Which questions this contract has to answer before a proposal on it may be saved.
     *
     * @param \App\Model\Entity\Contract $contract The contract, with its equipment and addresses.
     * @return array<string> The questions, by the name the confirmations know them under.
     */
    public function questionsFor(Contract $contract): array
    {
        $asked = [];

        if ($this->wantsBorrowedEquipment($contract)) {
            $asked[] = ProposalConfirmations::OWN_EQUIPMENT;
        }

        if ($this->wantsIpAddresses($contract)) {
            $asked[] = ProposalConfirmations::NO_IP_ADDRESSES;
        }

        if ($this->wantsARadiusAccount($contract)) {
            $asked[] = ProposalConfirmations::NO_RADIUS;
        }

        return $asked;
    }

    /**
     * What the operator has been asked but has not answered.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @param \App\Contracts\Proposal\ProposalConfirmations $answers What has been confirmed.
     * @return array<string>
     */
    public function unansweredFor(Contract $contract, ProposalConfirmations $answers): array
    {
        return $answers->unanswered($this->questionsFor($contract));
    }

    /**
     * What to say about each question, in the words the operator is answering.
     *
     * @return array<string, string>
     */
    public static function wording(): array
    {
        return [
            ProposalConfirmations::OWN_EQUIPMENT => __(
                'A borrowed equipment is not assigned, although it should normally be for this type'
                . ' of service. Please confirm that the customer has their own equipment or add it.',
            ),
            ProposalConfirmations::NO_IP_ADDRESSES => __(
                'IP addresses are not assigned, although they usually should be for this type of'
                . ' service. Please confirm that the customer does not use IP addresses or add them.',
            ),
            ProposalConfirmations::NO_RADIUS => __(
                'RADIUS accounts are not assigned, although they usually should be for this type of'
                . ' service. Please confirm that the customer does not use RADIUS accounts or add them.',
            ),
        ];
    }

    /**
     * Whether the contract is one that normally has equipment lent to it, and has none.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @return bool
     */
    private function wantsBorrowedEquipment(Contract $contract): bool
    {
        return $contract->service_type->have_equipments
            && $contract->service_type->normally_with_borrowed_equipment
            && empty($contract->borrowed_equipments);
    }

    /**
     * Whether the contract is one that normally has addresses, and has none.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @return bool
     */
    private function wantsIpAddresses(Contract $contract): bool
    {
        if (!$contract->service_type->have_ip_addresses) {
            return false;
        }

        return $this->addressesOfKind($contract, IpAddressTypeOfUse::CustomerRADIUS)->isEmpty()
            && $this->addressesOfKind($contract, IpAddressTypeOfUse::CustomerManually)->isEmpty();
    }

    /**
     * Whether the contract is one that normally has an account, and has none running.
     *
     * A network management system that cannot be reached is not an answer either way, so nothing is
     * asked about it - the same as at printing before.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @return bool
     */
    private function wantsARadiusAccount(Contract $contract): bool
    {
        if (
            !$contract->service_type->have_ip_addresses
            || !$contract->service_type->have_radius_accounts
            || $this->addressesOfKind($contract, IpAddressTypeOfUse::CustomerRADIUS)->isEmpty()
        ) {
            return false;
        }

        try {
            return $this->fetchTable(AccountsTable::class)
                ->find()
                ->where(['contract_id' => $contract->id, 'active' => true])
                ->limit(1)
                ->count() === 0;
        } catch (MissingConnectionException) {
            return false;
        }
    }

    /**
     * The contract's addresses of one kind.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @param \App\Model\Enum\IpAddressTypeOfUse $kind Which kind.
     * @return \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\IpAddress>
     */
    private function addressesOfKind(Contract $contract, IpAddressTypeOfUse $kind): iterable
    {
        return (new Collection($contract->ip_addresses ?? []))
            ->filter(fn(IpAddress $address): bool => $address->type_of_use === $kind);
    }
}
