<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalSnapshot;
use App\Contracts\Proposal\ProposalSnapshotBuilder;
use App\Contracts\Proposal\SnapshotShape;
use App\Model\Entity\Billing;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\ContractDocumentType;
use App\Test\Traits\ContractPapersTrait;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
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
    use ContractPapersTrait;
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
     * What the contract and its kind of service say beyond the standard terms. Two paragraphs
     * on the contract, because the printing splits them on the blank line between.
     */
    private const INDIVIDUAL_TERMS = <<<'TEXT'
        The discount holds for the whole term.

        Mounting on the mast is included in the price.
        TEXT;

    private const SERVICE_TERMS = 'The service is provided without contention.';

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
     * Puts the things the fixtures are thin on onto the contract, so that the comparison below
     * actually walks through them.
     *
     * @return void
     */
    private function fillOutTheContract(): void
    {
        $this->furnishTheContract();
        $this->agreeTerms();
    }

    /**
     * Terms on both the contract and the kind of service it is.
     *
     * @param string|null $terms What the contract itself says, or null to say nothing.
     * @return void
     */
    private function agreeTerms(?string $terms = self::INDIVIDUAL_TERMS): void
    {
        $contracts = $this->getTableLocator()->get('Contracts');
        $contract = $contracts->get(self::CONTRACT_ID);
        $contract->set('individual_terms', $terms);
        $contracts->saveOrFail($contract, ['checkRules' => false]);

        $types = $this->getTableLocator()->get('ServiceTypes');
        $type = $types->get($contract->get('service_type_id'));
        $type->set('service_terms', $terms === null ? null : self::SERVICE_TERMS);
        $types->saveOrFail($type, ['checkRules' => false]);
    }

    /**
     * Every document a proposal may be printed as.
     *
     * The papers the other side writes are not among them - nobody here draws a notice of
     * termination or a death certificate, so there is nothing for a snapshot to print alike.
     *
     * @return array<array{\App\Model\Enum\ContractDocumentType}>
     */
    public static function documents(): array
    {
        return array_map(
            fn(ContractDocumentType $type): array => [$type],
            array_values(array_filter(
                ContractDocumentType::cases(),
                fn(ContractDocumentType $type): bool => $type->canBeGenerated(),
            )),
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
     * @param \App\Model\Enum\ContractDocumentType $type Which document.
     * @return void
     */
    #[DataProvider('documents')]
    public function testADocumentPrintsTheSameFromASnapshot(ContractDocumentType $type): void
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
     * What was agreed beyond the standard terms belongs on the contract itself and nowhere else.
     *
     * The paper is compared with and without the terms rather than read for the words: the text
     * goes into the PDF through a subset font, so what comes out is glyph numbers. A document that
     * prints them cannot come out the same as one that does not, and one that does not must.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Which document.
     * @return void
     */
    #[DataProvider('documents')]
    public function testTheAgreedTermsReachTheContractAndNoOtherDocument(ContractDocumentType $type): void
    {
        $this->fillOutTheContract();

        $version = $this->liveVersion();

        $this->agreeTerms(null);
        $without = $this->print($this->printData($type, $this->liveContract(), $version));

        $this->agreeTerms();
        $with = $this->print($this->printData($type, $this->liveContract(), $version));

        $carries = $type === ContractDocumentType::ContractNew || $type === ContractDocumentType::ContractNewX;

        if ($carries) {
            $this->assertNotSame(
                $without,
                $with,
                sprintf('The %s says nothing about what was agreed.', $type->value),
            );

            return;
        }

        $this->assertSame(
            $without,
            $with,
            sprintf('The %s prints what was agreed, and it has no business saying it.', $type->value),
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

    /**
     * A day nobody has touched has not moved, whatever the office reads dates as.
     *
     * The snapshot keeps the day it is and the record hands back an object that writes itself out
     * in the local order, so comparing the two as plain strings held 2026-12-01 up against
     * 01.12.2026 and reported every date of every proposal as having been edited since.
     *
     * @return void
     */
    public function testADayNobodyMovedIsNotReportedAsMoved(): void
    {
        $snapshot = ProposalSnapshot::fromArray([
            'contract' => [],
            'customer' => [],
            'version' => ['valid_until' => '2026-09-30', 'conclusion_date' => null],
            'billings' => [[
                'id' => 'b1',
                'quantity' => 1,
                'billing_from' => '2026-12-01',
                'billing_until' => null,
            ]],
        ]);

        $live = new Billing([
            'id' => 'b1',
            'quantity' => 1,
            'billing_from' => new Date('2026-12-01'),
            'billing_until' => null,
        ]);

        $this->assertSame([], $snapshot->billingTermsThatMoved('b1', $live));
        $this->assertSame([], $snapshot->versionTermsThatMoved(new ContractVersion([
            'valid_until' => new Date('2026-09-30'),
            'conclusion_date' => null,
        ])));
    }

    /**
     * A day that really did move is still reported.
     *
     * @return void
     */
    public function testADayThatMovedIsReported(): void
    {
        $snapshot = ProposalSnapshot::fromArray([
            'contract' => [],
            'customer' => [],
            'version' => ['valid_until' => '2026-09-30'],
            'billings' => [['id' => 'b1', 'billing_from' => '2026-12-01']],
        ]);

        $this->assertSame(
            ['billing_from'],
            $snapshot->billingTermsThatMoved('b1', new Billing([
                'id' => 'b1',
                'billing_from' => new Date('2027-01-01'),
            ])),
        );
        $this->assertSame(
            ['valid_until'],
            $snapshot->versionTermsThatMoved(new ContractVersion(['valid_until' => new Date('2026-10-31')])),
        );
    }

    /**
     * The service of a billing is held up against the live one too, and it is kept whole rather
     * than as an id - the papers print its name - so that is where its id is looked for.
     *
     * @return void
     */
    public function testAServiceSomebodyChangedIsReportedAsMoved(): void
    {
        $snapshot = ProposalSnapshot::fromArray([
            'contract' => [],
            'customer' => [],
            'version' => [],
            'billings' => [[
                'id' => 'b1',
                'service' => ['id' => 'aaaaaaaa-0000-4000-8000-000000000001', 'name' => 'Internet'],
            ]],
        ]);

        $this->assertSame([], $snapshot->billingTermsThatMoved('b1', new Billing([
            'id' => 'b1',
            'service_id' => 'aaaaaaaa-0000-4000-8000-000000000001',
        ])));

        $this->assertSame(
            ['service_id'],
            $snapshot->billingTermsThatMoved('b1', new Billing([
                'id' => 'b1',
                'service_id' => 'bbbbbbbb-0000-4000-8000-000000000002',
            ])),
        );
    }

    /**
     * A term the snapshot never kept is passed over rather than read as having moved, or every
     * proposal drawn before the shape grew would carry the same false alarm for ever.
     *
     * @return void
     */
    public function testATermTheSnapshotNeverKeptHasNotMoved(): void
    {
        $snapshot = ProposalSnapshot::fromArray([
            'contract' => [],
            'customer' => [],
            'version' => [],
            // Nothing is said about the service at all, which is a snapshot from before the
            // shape kept one.
            'billings' => [['id' => 'b1', 'quantity' => 1]],
        ]);

        $this->assertSame([], $snapshot->billingTermsThatMoved('b1', new Billing([
            'id' => 'b1',
            'quantity' => 1,
            'service_id' => '9923d0dc-afe6-4ecb-a578-48b046cd73d3',
        ])));
    }
}
