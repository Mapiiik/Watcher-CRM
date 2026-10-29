<?php
declare(strict_types=1);

namespace App\Test\TestCase\Pdf;

use App\Contracts\Proposal\ProjectedEquipment;
use App\Model\Entity\BorrowedEquipment;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\EquipmentType;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\ProposalPurpose;
use App\Pdf\ContractPDF;
use App\Pdf\ContractSummaryPDF;
use App\Service\ContractPrint\ContractPrintData;
use App\Test\Traits\ContractPapersTrait;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * What the papers say depending on what their proposal asks for.
 *
 * Compared as whole documents rather than read for words: the text goes into the PDF through a
 * subset font, so what comes out is glyph numbers.
 */
#[UsesClass(ContractPDF::class)]
#[UsesClass(ContractSummaryPDF::class)]
class ProposalPapersTest extends TestCase
{
    use ContractPapersTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Emails',
        'app.Phones',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.IpNetworks',
        'plugin.Settings.Settings',
    ];

    /**
     * A contract replacing an earlier one takes the connection over as it is, so its papers print
     * as if there were no fee at all - unless the operator said the connection is set up again,
     * and then they charge it the way a new contract does.
     *
     * @return void
     */
    public function testAReplacingContractChargesTheActivationOnlyWhenAsked(): void
    {
        $this->furnishTheContract();

        $papers = [
            ContractDocumentType::ContractNewX,
            ContractDocumentType::ContractSummary,
        ];

        foreach ([false, true] as $committed) {
            foreach ($papers as $type) {
                $taken = $this->printed($type, reactivates: false, committed: $committed);
                $withoutAFee = $this->printed($type, reactivates: false, committed: $committed, fee: '0');
                $charged = $this->printed($type, reactivates: true, committed: $committed);

                $this->assertSame($withoutAFee, $taken, $type->value . ' charged a fee nobody asked for.');
                $this->assertNotSame($taken, $charged, $type->value . ' did not charge the fee it was asked to.');
            }
        }

        // the protocol leaves the fee out rather than writing it down as nothing
        $this->assertNotSame(
            $this->printed(ContractDocumentType::HandoverInstallation, reactivates: false, fee: '0'),
            $this->printed(ContractDocumentType::HandoverInstallation, reactivates: true, fee: '0'),
        );
    }

    /**
     * A new contract charges the activation whatever the box says, because there is nothing to
     * take over.
     *
     * @return void
     */
    public function testANewContractAlwaysChargesTheActivation(): void
    {
        $this->furnishTheContract();

        $data = $this->printData(ContractDocumentType::ContractNew, $this->liveContract(), $this->liveVersion());
        $data->proposal = new ContractProposal(['purpose' => ProposalPurpose::NewContract]);

        $this->assertTrue($data->activatesTheService());
    }

    /**
     * Where nothing moves, the projection says what the snapshot says, so a paper printed with one
     * is the paper printed without. That is what keeps every paper without equipment lines as it
     * was.
     *
     * @return void
     */
    public function testEquipmentThatStaysPrintsAsItDid(): void
    {
        $this->furnishTheContract();

        $newContract = [
            ContractDocumentType::ContractNew,
            ContractDocumentType::ContractSummary,
            ContractDocumentType::HandoverInstallation,
            ContractDocumentType::HandoverUninstallation,
        ];

        foreach ($newContract as $type) {
            $this->assertSame(
                $this->printedForANewContract($type, projected: false),
                $this->printedForANewContract($type, projected: true),
                $type->value . ' printed differently once the equipment was projected.',
            );
        }

        foreach ([ContractDocumentType::ContractNewX, ContractDocumentType::ContractSummary] as $type) {
            $contract = $this->liveContract();
            $version = $this->liveVersion();
            $version->set('valid_until', null);

            $without = $this->replacing($type, $contract, $version, reactivates: false);
            $with = $this->replacing($type, $contract, $version, reactivates: false);
            $with->projectedEquipment = new ProjectedEquipment($contract->borrowed_equipments, [], []);

            $this->assertSame($this->print($without), $this->print($with), $type->value . ' moved.');
        }
    }

    /**
     * A box swapped by a replacing contract shows on the contract, which lists what is added, and
     * on the exchange protocol, which lists what goes back and what comes in its place.
     *
     * @return void
     */
    public function testASwappedBoxIsOnTheContractAndTheExchangeProtocol(): void
    {
        $this->furnishTheContract();

        foreach ([ContractDocumentType::ContractNewX, ContractDocumentType::HandoverExchange] as $type) {
            $contract = $this->liveContract();
            $version = $this->liveVersion();
            $version->set('valid_until', null);

            $asItWas = $this->replacing($type, $contract, $version, reactivates: false);
            $swapped = $this->replacing($type, $contract, $version, reactivates: false);
            $swapped->projectedEquipment = new ProjectedEquipment([], [$this->anotherBox()], $contract->borrowed_equipments);

            $this->assertNotSame($this->print($asItWas), $this->print($swapped), $type->value . ' did not change.');
        }
    }

    /**
     * A contract replacing an earlier one hands over only what it adds - the rest is with the
     * customer already - so its installation protocol is the one a new contract lending only
     * that would print.
     *
     * @return void
     */
    public function testAReplacingContractHandsOverOnlyWhatItAdds(): void
    {
        $this->furnishTheContract();

        $contract = $this->liveContract();
        $version = $this->liveVersion();
        $version->set('valid_until', null);
        $added = $this->anotherBox();

        $replacing = $this->replacing(ContractDocumentType::HandoverInstallation, $contract, $version, reactivates: true);
        $replacing->projectedEquipment = new ProjectedEquipment($contract->borrowed_equipments, [$added], []);

        $lendingOnlyThat = $this->liveContract();
        $lendingOnlyThat->set('borrowed_equipments', [$added]);
        $new = $this->printData(ContractDocumentType::HandoverInstallation, $lendingOnlyThat, $version);
        $new->proposal = new ContractProposal(['purpose' => ProposalPurpose::NewContract]);

        $this->assertSame($this->print($new), $this->print($replacing));
    }

    /**
     * One paper of a new contract, with its equipment projected as staying or not projected at all.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Which paper.
     * @param bool $projected Whether the equipment is projected.
     * @return string
     */
    private function printedForANewContract(ContractDocumentType $type, bool $projected): string
    {
        $contract = $this->liveContract();
        $version = $this->liveVersion();
        $version->set('valid_until', null);

        $data = $this->printData($type, $contract, $version);
        $data->proposal = new ContractProposal(['purpose' => ProposalPurpose::NewContract]);

        if ($projected) {
            $data->projectedEquipment = new ProjectedEquipment($contract->borrowed_equipments, [], []);
        }

        return $this->print($data);
    }

    /**
     * A box of another kind, as a line adding it brings it.
     *
     * @return \App\Model\Entity\BorrowedEquipment
     */
    private function anotherBox(): BorrowedEquipment
    {
        return new BorrowedEquipment([
            'serial_number' => 'SN-NEW-1',
            'equipment_type' => new EquipmentType(['name' => 'Another box', 'price' => Decimal::create('2500', 2)]),
        ]);
    }

    /**
     * One paper of a contract replacing an earlier version.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Which paper.
     * @param bool $reactivates Whether the connection is set up again.
     * @param bool $committed Whether the version carries a minimum term.
     * @param string|null $fee The activation fee to charge, where it is not the contract's own.
     * @return string
     */
    private function printed(
        ContractDocumentType $type,
        bool $reactivates,
        bool $committed = false,
        ?string $fee = null,
    ): string {
        $contract = $this->liveContract();
        $version = $this->liveVersion();

        $this->withAFee($contract, $fee ?? '2000');
        $this->bound($version, $committed);

        return $this->print($this->replacing($type, $contract, $version, $reactivates));
    }

    /**
     * What to print for a contract replacing an earlier version.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Which paper.
     * @param \App\Model\Entity\Contract $contract The contract.
     * @param \App\Model\Entity\ContractVersion $version The version.
     * @param bool $reactivates Whether the connection is set up again.
     * @return \App\Service\ContractPrint\ContractPrintData
     */
    private function replacing(
        ContractDocumentType $type,
        Contract $contract,
        ContractVersion $version,
        bool $reactivates,
    ): ContractPrintData {
        $data = $this->printData($type, $contract, $version);
        $data->proposal = new ContractProposal([
            'purpose' => ProposalPurpose::NewContract,
            'terminates_contract_version_id' => self::VERSION_ID,
            'reactivates' => $reactivates,
        ]);

        return $data;
    }

    /**
     * Puts an activation fee on the contract, discounted to almost nothing for a commitment.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @param string $fee The full fee.
     * @return void
     */
    private function withAFee(Contract $contract, string $fee): void
    {
        $contract->set('activation_fee', Decimal::create($fee, 2));
        $contract->set('activation_fee_with_obligation', Decimal::create($fee === '0' ? '0' : '1', 2));
    }

    /**
     * Makes the version an indefinite one with a minimum term of two years, or with none.
     *
     * @param \App\Model\Entity\ContractVersion $version The version.
     * @param bool $committed Whether it carries one.
     * @return void
     */
    private function bound(ContractVersion $version, bool $committed): void
    {
        $version->set('valid_until', null);
        $version->set('obligation_until', $committed ? $version->valid_from->addMonths(24)->subDays(1) : null);
    }
}
