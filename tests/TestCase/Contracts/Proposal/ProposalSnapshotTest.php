<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalSnapshot;
use App\Contracts\Proposal\ProposalSnapshotBuilder;
use App\Contracts\Proposal\SnapshotShape;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\AddressType;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Pdf\ContractPDF;
use App\Pdf\ContractSummaryPDF;
use App\Service\ContractPrint\ContractPrintData;
use App\Service\ContractPrint\ContractPrintDataEnricher;
use App\Test\Traits\TableTestTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Proposal\ProposalSnapshot Test Case
 */
#[CoversClass(ProposalSnapshot::class)]
#[UsesClass(ProposalSnapshotBuilder::class)]
#[UsesClass(SnapshotShape::class)]
class ProposalSnapshotTest extends TestCase
{
    use TableTestTrait;

    /**
     * The contract everything hangs off.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Its customer.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * A concluded version of it.
     */
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
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
        'app.Queues',
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
     * Puts the things the fixtures are thin on onto the contract, so that the comparison below
     * actually walks through them.
     *
     * @return void
     */
    private function fillOutTheContract(): void
    {
        $this->put('Emails', [
            'customer_id' => self::CUSTOMER_ID,
            'email' => 'lorem@example.com',
            'use_for_billing' => true,
        ]);

        $this->put('Phones', [
            'customer_id' => self::CUSTOMER_ID,
            'phone' => '+420601123456',
            'use_for_billing' => true,
        ]);

        foreach ([AddressType::Permanent, AddressType::Delivery, AddressType::Billing] as $type) {
            $this->put('Addresses', [
                'customer_id' => self::CUSTOMER_ID,
                'type' => $type,
                'first_name' => 'Lorem',
                'last_name' => 'Ipsum',
                'street' => 'Dolor',
                'number' => '1',
                'number_type' => 0,
                'city' => 'Sit',
                'zip' => '100 00',
                'country_id' => 'b490f1c9-ff7e-430a-bfb0-f400878e1617',
            ]);
        }

        $equipmentTypeId = $this->getTableLocator()->get('EquipmentTypes')
            ->find()->firstOrFail()->get('id');

        $this->put('BorrowedEquipments', [
            'contract_id' => self::CONTRACT_ID,
            'customer_id' => self::CUSTOMER_ID,
            'equipment_type_id' => $equipmentTypeId,
            'serial_number' => 'SN-BORROWED-1',
            'borrowed_from' => '2022-01-01',
        ]);

        $this->put('IpAddresses', [
            'contract_id' => self::CONTRACT_ID,
            'customer_id' => self::CUSTOMER_ID,
            'ip_address' => '192.0.2.10',
            'type_of_use' => IpAddressTypeOfUse::CustomerRADIUS,
        ]);

        $this->put('IpNetworks', [
            'contract_id' => self::CONTRACT_ID,
            'customer_id' => self::CUSTOMER_ID,
            'ip_network' => '192.0.2.0/29',
            'type_of_use' => IpAddressTypeOfUse::CustomerRADIUS->value,
        ]);
    }

    /**
     * A record put there for the test, the way a fixture would put it there.
     *
     * Fixtures go straight into the database, so they are held to nothing the forms are held to;
     * these stand in for fixtures, so they are not held to it either.
     *
     * @param string $table Which records it is.
     * @param array<string, mixed> $record What it says.
     * @return void
     */
    private function put(string $table, array $record): void
    {
        $records = $this->getTableLocator()->get($table);
        $entity = $records->newEntity($record, ['validate' => false]);

        $records->saveOrFail($entity, ['checkRules' => false]);
    }

    /**
     * The contract loaded the way printing loads it.
     *
     * @return \App\Model\Entity\Contract
     */
    private function liveContract(): Contract
    {
        /** @var \App\Model\Entity\Contract $contract */
        $contract = $this->getTableLocator()->get('Contracts')->get(self::CONTRACT_ID, contain: [
            'Billings' => ['Services' => ['Queues']],
            'BorrowedEquipments.EquipmentTypes' => fn(SelectQuery $q): SelectQuery => $q->where([
                'BorrowedEquipments.borrowed_until IS NULL',
            ]),
            'ContractStates',
            'ContractVersions',
            'Customers' => ['Addresses', 'Emails', 'Phones', 'AccountingProfiles'],
            'InstallationAddresses',
            'IpAddresses',
            'IpNetworks',
            'ServiceTypes',
            'SoldEquipments.EquipmentTypes' => fn(SelectQuery $q): SelectQuery => $q->where([
                'SoldEquipments.date_of_sale IS NULL',
            ]),
        ]);

        return $contract;
    }

    /**
     * The version the papers are drawn up for.
     *
     * @return \App\Model\Entity\ContractVersion
     */
    private function liveVersion(): ContractVersion
    {
        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->getTableLocator()->get('ContractVersions')->get(self::VERSION_ID);

        return $version;
    }

    /**
     * The document, as bytes, with the parts that differ between two runs of the same data taken
     * out - the creation stamp and the file identifier.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data What to print.
     * @return string
     */
    private function print(ContractPrintData $data): string
    {
        (new ContractPrintDataEnricher())->enrich($data, []);

        $pdf = $data->type === ContractPrintType::ContractSummary
            ? new ContractSummaryPDF('P', 'mm', 'A4')
            : new ContractPDF('P', 'mm', 'A4');

        $pdf->setDocCreationTimestamp(0);
        $pdf->setDocModificationTimestamp(0);

        match (true) {
            $pdf instanceof ContractSummaryPDF => $pdf->generateContractSummary($data),
            $data->type->isHandoverProtocol() => $pdf->generateHandoverProtocol($data),
            default => $pdf->generateContract($data),
        };

        $printed = $pdf->Output('test.pdf', 'S');

        return (string)preg_replace(
            ['#/ID\s*\[[^\]]*\]#', '#uuid:[0-9a-f-]{36}#'],
            ['/ID []', 'uuid:0'],
            $printed,
        );
    }

    /**
     * What to print, for a contract and the version the papers are for.
     *
     * @param \App\Model\Enum\ContractPrintType $type Which document.
     * @param \App\Model\Entity\Contract $contract The contract.
     * @param \App\Model\Entity\ContractVersion $version The version.
     * @return \App\Service\ContractPrint\ContractPrintData
     */
    private function printData(
        ContractPrintType $type,
        Contract $contract,
        ContractVersion $version,
    ): ContractPrintData {
        $data = new ContractPrintData(
            type: $type,
            contract: $contract,
            contractVersionToBeExecuted: $version,
            contractVersionToBeTerminated: $version,
        );
        $data->contractNumberToBeTerminated = 'Lorem ipsum dolor sit amet';
        $data->effectiveDateOfAmendment = $version->valid_from;

        return $data;
    }

    /**
     * Every document a proposal may be printed as.
     *
     * @return array<array{\App\Model\Enum\ContractPrintType}>
     */
    public static function documents(): array
    {
        return array_map(
            fn(ContractPrintType $type): array => [$type],
            ContractPrintType::cases(),
        );
    }

    /**
     * The whole point of keeping a snapshot: a document printed from it is the document that would
     * have been printed from the live records at the moment it was taken.
     *
     * This is the only thing standing between an incomplete snapshot and finding out about it in
     * production, because a field the documents read and the snapshot does not carry shows up
     * nowhere else. When it fails, the fix is a field in SnapshotShape, not a looser comparison.
     *
     * @param \App\Model\Enum\ContractPrintType $type Which document.
     * @return void
     */
    #[DataProvider('documents')]
    public function testADocumentPrintsTheSameFromASnapshot(ContractPrintType $type): void
    {
        $this->fillOutTheContract();

        $live = $this->liveContract();
        $version = $this->liveVersion();

        $taken = (new ProposalSnapshotBuilder())->take($live, $version, $version);
        $snapshot = ProposalSnapshot::fromArray($taken);

        $fromLive = $this->print($this->printData($type, $live, $version));
        $fromSnapshot = $this->print($this->printData(
            $type,
            $snapshot->hydrate(),
            $snapshot->hydrateVersion(),
        ));

        $this->assertSame(
            strlen($fromLive),
            strlen($fromSnapshot),
            sprintf('The %s printed from the snapshot is a different length.', $type->value),
        );
        $this->assertSame(
            $fromLive,
            $fromSnapshot,
            sprintf('The %s printed from the snapshot is not the one printed live.', $type->value),
        );
    }

    /**
     * A snapshot missing a part the documents rely on is refused rather than printed from.
     *
     * @return void
     */
    public function testAnIncompleteSnapshotIsRefused(): void
    {
        $this->expectExceptionMessage('The snapshot says nothing about billings.');

        ProposalSnapshot::fromArray([
            'contract' => [],
            'customer' => [],
            'version' => [],
        ]);
    }
}
