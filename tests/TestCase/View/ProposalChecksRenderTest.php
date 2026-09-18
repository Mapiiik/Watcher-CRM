<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\Customer;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\ProposalPurpose;
use App\View\AppView;
use Authorization\AuthorizationServiceInterface;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The four checks on proposals draw one table between them, each with its own days.
 */
class ProposalChecksRenderTest extends TestCase
{
    private const CREATED = '2026-09-01 10:00:00';
    private const SENT = '2026-09-05';
    private const CONCLUDED = '2026-09-12';

    /**
     * The rows lead to the contract and the customer, so the addresses have to be known.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadRoutes();
    }

    /**
     * @return array<string, array{0: string, 1: list<\Cake\I18n\Date|\Cake\I18n\DateTime>, 2: list<string>}>
     */
    public static function checks(): array
    {
        return [
            'unsent' => [
                'unsent_proposal',
                [new DateTime(self::CREATED)],
                ['documents/manage', 'customer-proposals/send/'],
            ],
            'unsigned' => ['unsigned_proposal', [new Date(self::SENT)], ['customer-proposals/conclude/']],
            'unfiled' => ['unfiled_signature', [new Date(self::CONCLUDED)], ['documents/manage']],
            'untransferred' => [
                'untransferred_proposal',
                [new Date(self::CONCLUDED), new Date(self::SENT)],
                ['customer-proposals/transfer/'],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function elements(): array
    {
        return array_map(fn(array $check): array => [$check[0]], self::checks());
    }

    /**
     * Every one of them names the contract and the customer, the days it is about and what may
     * be done next.
     *
     * @param string $element The check's element.
     * @param list<\Cake\I18n\Date|\Cake\I18n\DateTime> $days The days the rows have to show.
     * @param list<string> $steps Where the rows have to lead.
     * @return void
     */
    #[DataProvider('checks')]
    public function testARowSaysWhoseItIsAndWhen(string $element, array $days, array $steps): void
    {
        $drawn = $this->view()->element('ContractChecks/' . $element, ['records' => [$this->proposal()]]);

        $this->assertStringContainsString('2026/0042', $drawn);
        $this->assertStringContainsString('Nested Customer', $drawn);
        $this->assertStringContainsString(h(ProposalPurpose::ServiceChange->label()), $drawn);

        // Said the way the page says any day, which is the locale's business rather than this test's.
        foreach ($days as $day) {
            $this->assertStringContainsString(h((string)$day), $drawn);
        }

        foreach ($steps as $step) {
            $this->assertStringContainsString($step, $drawn);
        }
    }

    /**
     * On the customer's own page the column that names them is left out.
     *
     * @param string $element The check's element.
     * @return void
     */
    #[DataProvider('elements')]
    public function testTheCustomerIsNotNamedOnTheirOwnPage(string $element): void
    {
        $drawn = $this->view()->element('ContractChecks/' . $element, [
            'records' => [$this->proposal()],
            'customer_column' => false,
        ]);

        $this->assertStringContainsString('2026/0042', $drawn);
        $this->assertStringNotContainsString('Nested Customer', $drawn);
    }

    /**
     * A view whose links are all allowed, since who may follow them is not what is asked here.
     *
     * @return \App\View\AppView
     */
    private function view(): AppView
    {
        $allowed = $this->createStub(AuthorizationServiceInterface::class);
        $allowed->method('can')->willReturn(true);

        return new AppView((new ServerRequest())->withAttribute('authorization', $allowed));
    }

    /**
     * One proposal, with everything the rows read.
     *
     * @return \App\Model\Entity\ContractProposal
     */
    private function proposal(): ContractProposal
    {
        $customer = new Customer([
            'id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
            'company' => 'Nested Customer',
        ]);

        return new ContractProposal([
            'id' => 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60',
            'customer_proposal_id' => 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34',
            'purpose' => ProposalPurpose::ServiceChange,
            'effective_from' => new Date('2026-10-01'),
            'created' => new DateTime(self::CREATED),
            'contract' => new Contract([
                'id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'customer_id' => $customer->id,
                'number' => '2026/0042',
                'customer' => $customer,
            ]),
            'customer_proposal' => new CustomerProposal([
                'id' => 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34',
                'sent_date' => new Date(self::SENT),
                'delivery_type' => DocumentsDeliveryType::Post,
                'conclusion_date' => new Date(self::CONCLUDED),
            ]),
        ]);
    }
}
