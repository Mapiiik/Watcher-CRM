<?php
declare(strict_types=1);

namespace App\Test\TestCase\Pdf;

use App\Contracts\Proposal\ProjectedEquipment;
use App\Model\Entity\BorrowedEquipment;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\EquipmentType;
use App\Model\Entity\SoldEquipment;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\CustomerLegalStatus;
use App\Model\Enum\ProposalPurpose;
use App\Pdf\ContractPDF;
use App\Pdf\ContractSummaryPDF;
use App\Pdf\SignatureAnchors;
use App\Service\ContractPrint\ContractPrintData;
use App\Test\Traits\ContractPapersTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Service\SettingsService;
use Settings\Utility\Settings;

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
     * The texts a legal entity has a wording of its own for.
     *
     * @var list<string>
     */
    private const CLAUSES_BY_LEGAL_STATUS = [
        'core.documents.contracts.contract.texts.activation_fee_clause',
        'core.documents.contracts.handover.texts.early_termination_clause',
        'core.documents.contracts.summary.texts.renewal_indefinite_committed',
        'core.documents.contracts.summary.texts.renewal_definite',
    ];

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
     * Forgets the texts a test set: the database is rolled back, but the settings' caches are not.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Settings::setInstance(new SettingsService());
        Cache::clear('default');

        parent::tearDown();
    }

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
     * Every handover protocol has lines for the day the work was done and for whoever took part
     * in it for the customer. The papers are printed ahead and not always signed on the day, so
     * neither date says when the equipment changed hands, and the signature need not be theirs.
     *
     * Told apart by changing what each protocol's own line says: the protocol has to change with it.
     *
     * @return void
     */
    public function testEveryHandoverProtocolHasLinesForTheWorkOnTheSpot(): void
    {
        $this->furnishTheContract();

        $lines = [
            'installation_date_line' => ContractDocumentType::HandoverInstallation,
            'installation_received_on_site' => ContractDocumentType::HandoverInstallation,
            'uninstallation_date_line' => ContractDocumentType::HandoverUninstallation,
            'uninstallation_handed_over_on_site' => ContractDocumentType::HandoverUninstallation,
            'exchange_date_line' => ContractDocumentType::HandoverExchange,
            'exchange_received_on_site' => ContractDocumentType::HandoverExchange,
        ];

        foreach ($lines as $line => $type) {
            $asWritten = $this->handoverProtocol($type);
            Settings::set('core.documents.contracts.handover.texts.' . $line, 'Lorem ipsum: ____');

            $this->assertNotSame($asWritten, $this->handoverProtocol($type), $type->value . ' has no ' . $line . '.');
        }
    }

    /**
     * One handover protocol of the kind given, the exchange one for a box swapped for another.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Which protocol.
     * @return string
     */
    private function handoverProtocol(ContractDocumentType $type): string
    {
        if ($type !== ContractDocumentType::HandoverExchange) {
            return $this->printedForANewContract($type, projected: false);
        }

        $contract = $this->liveContract();
        $version = $this->liveVersion();
        $version->set('valid_until', null);

        $data = $this->replacing($type, $contract, $version, reactivates: false);
        $data->projectedEquipment = new ProjectedEquipment([], [$this->anotherBox()], $contract->borrowed_equipments);

        return $this->print($data);
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
     * A legal entity is held to what was agreed, so its papers print the clauses written for it -
     * on the contract, the summary of both shapes of term, and the installation protocol.
     *
     * Told apart by making the clauses for a legal entity say what everybody else's say: the
     * papers have to change, or they never printed them.
     *
     * @return void
     */
    public function testALegalEntityGetsTheClausesWrittenForIt(): void
    {
        $this->furnishTheContract();

        $asWritten = $this->everyClausedPaper(CustomerLegalStatus::LegalEntity);

        foreach (self::CLAUSES_BY_LEGAL_STATUS as $path) {
            Settings::set($path . '_legal_entity', Settings::getString($path));
        }

        foreach ($this->everyClausedPaper(CustomerLegalStatus::LegalEntity) as $paper => $printed) {
            $this->assertNotSame($asWritten[$paper], $printed, $paper . ' did not print the clause for a legal entity.');
        }
    }

    /**
     * Everybody else - a natural person, a sole trader, and a customer nobody has said anything
     * about - gets the clauses the law allows to be asked of a natural person, whatever the ones
     * for a legal entity say.
     *
     * @return void
     */
    public function testEverybodyElseGetsTheClausesForANaturalPerson(): void
    {
        $this->furnishTheContract();

        $legalStatuses = [CustomerLegalStatus::NaturalPerson, CustomerLegalStatus::SoleTrader, null];

        $asWritten = [];
        foreach ($legalStatuses as $legalStatus) {
            $asWritten[] = $this->everyClausedPaper($legalStatus);
        }

        foreach (self::CLAUSES_BY_LEGAL_STATUS as $path) {
            Settings::set($path . '_legal_entity', 'Lorem ipsum dolor sit amet.');
        }

        foreach ($legalStatuses as $index => $legalStatus) {
            $this->assertSame(
                $asWritten[$index],
                $this->everyClausedPaper($legalStatus),
                ($legalStatus->value ?? 'nobody') . ' printed a clause for a legal entity.',
            );
        }
    }

    /**
     * The summary carries a line for the customer to sign only where the deployment asks for one,
     * and never one for the provider.
     *
     * @return void
     */
    public function testTheSummaryHasALineForTheCustomerOnlyWhereAsked(): void
    {
        $this->furnishTheContract();

        $anchors = fn(): SignatureAnchors => SignatureAnchors::fromPdf($this->printedForACustomer(
            ContractDocumentType::ContractSummary,
            CustomerLegalStatus::NaturalPerson,
        ));

        $this->assertNull($anchors()->find(SignatureAnchors::CUSTOMER_SIGNATURE));

        Settings::set('core.documents.contracts.summary.customer_signature', true);

        $signed = $anchors();
        $this->assertNotNull($signed->find(SignatureAnchors::CUSTOMER_SIGNATURE));
        $this->assertNull($signed->find(SignatureAnchors::PROVIDER_SIGNATURE));
    }

    /**
     * A consumer is told about withdrawing and asked for consent on the papers that make or change
     * a contract. A sole trader, a legal entity and an ending get none of it.
     *
     * @return void
     */
    public function testOnlyAConsumerIsToldAboutWithdrawal(): void
    {
        $this->furnishTheContract();

        $papers = fn(): array => [
            'natural person, new' => $this->printedForACustomer(
                ContractDocumentType::ContractNew,
                CustomerLegalStatus::NaturalPerson,
            ),
            'nobody, new' => $this->printedForACustomer(ContractDocumentType::ContractNew, null),
            'natural person, amendment' => $this->printedForACustomer(
                ContractDocumentType::ContractAmendment,
                CustomerLegalStatus::NaturalPerson,
            ),
            'sole trader, new' => $this->printedForACustomer(
                ContractDocumentType::ContractNew,
                CustomerLegalStatus::SoleTrader,
            ),
            'legal entity, new' => $this->printedForACustomer(
                ContractDocumentType::ContractNew,
                CustomerLegalStatus::LegalEntity,
            ),
            'natural person, termination' => $this->printedForACustomer(
                ContractDocumentType::ContractTermination,
                CustomerLegalStatus::NaturalPerson,
            ),
        ];

        $without = $papers();

        Settings::set('core.documents.contracts.contract.sections.withdrawal', 'Lorem ipsum');
        Settings::set(
            'core.documents.contracts.contract.texts.withdrawal_text',
            "Lorem ipsum dolor sit amet.\n\n▢ yes / ▢ no - consectetur adipiscing elit.",
        );
        Settings::set(
            'core.documents.contracts.contract.texts.special_status_consent_text',
            '▢ yes / ▢ no - sed do eiusmod tempor.',
        );
        Settings::set('core.documents.contracts.contract.texts.withdrawal_attachment_html', '<p>Lorem ipsum.</p>');

        $with = $papers();

        foreach (['natural person, new', 'nobody, new', 'natural person, amendment'] as $paper) {
            $this->assertNotSame($without[$paper], $with[$paper], $paper . ' was not told about withdrawal.');
        }

        foreach (['sole trader, new', 'legal entity, new', 'natural person, termination'] as $paper) {
            $this->assertSame($without[$paper], $with[$paper], $paper . ' was told about withdrawal.');
        }
    }

    /**
     * The notice with its form takes a page of its own, so that the form can be sent back, and
     * nothing else makes the paper longer.
     *
     * @return void
     */
    public function testTheWithdrawalFormIsAPageOfItsOwn(): void
    {
        $this->furnishTheContract();

        $pages = fn(): int => (int)preg_match_all('#/Type\s*/Page\b(?!s)#', $this->printedForACustomer(
            ContractDocumentType::ContractNew,
            CustomerLegalStatus::NaturalPerson,
        ));

        $before = $pages();

        Settings::set('core.documents.contracts.contract.texts.withdrawal_attachment_html', '<p>Lorem ipsum.</p>');

        $this->assertSame($before + 1, $pages());
    }

    /**
     * Every paper that prints a clause on what leaving early costs, for a new contract committing
     * the customer and selling them a router at a discount, made out to a customer of the legal
     * status given.
     *
     * @param \App\Model\Enum\CustomerLegalStatus|null $legalStatus Who the customer is in law.
     * @return array<string, string> The papers, by what they are.
     */
    private function everyClausedPaper(?CustomerLegalStatus $legalStatus): array
    {
        $papers = [];

        foreach (['indefinite' => false, 'definite' => true] as $term => $definite) {
            foreach ([ContractDocumentType::ContractNew, ContractDocumentType::ContractSummary] as $type) {
                $papers[$type->value . ', ' . $term] = $this->printedForACustomer($type, $legalStatus, $definite);
            }
        }

        $papers[ContractDocumentType::HandoverInstallation->value] = $this->printedForACustomer(
            ContractDocumentType::HandoverInstallation,
            $legalStatus,
        );

        return $papers;
    }

    /**
     * One paper of a new contract committing the customer, with a router sold at a discount.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Which paper.
     * @param \App\Model\Enum\CustomerLegalStatus|null $legalStatus Who the customer is in law.
     * @param bool $definite Whether the term is a fixed one rather than a minimum one.
     * @return string
     */
    private function printedForACustomer(
        ContractDocumentType $type,
        ?CustomerLegalStatus $legalStatus,
        bool $definite = false,
    ): string {
        $contract = $this->liveContract();
        $contract->customer->legal_status = $legalStatus;
        $contract->set('sold_equipments', [
            new SoldEquipment([
                'serial_number' => 'SN-SOLD-1',
                'equipment_type' => new EquipmentType([
                    'name' => 'Router',
                    'price' => Decimal::create('1500', 2),
                    'price_with_obligation' => Decimal::create('1', 2),
                ]),
            ]),
        ]);
        $this->withAFee($contract, '2000');

        $version = $this->liveVersion();
        $this->bound($version, committed: true);
        if ($definite) {
            $version->set('valid_until', $version->obligation_until);
        }

        $data = $this->printData($type, $contract, $version);
        $data->proposal = new ContractProposal(['purpose' => ProposalPurpose::NewContract]);

        return $this->print($data);
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
