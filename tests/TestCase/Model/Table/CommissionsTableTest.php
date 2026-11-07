<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CommissionsTable;
use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\CommissionsTable Test Case
 */
class CommissionsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\CommissionsTable
     */
    protected $Commissions;

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
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.DealerCommissions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Commissions') ? [] : ['className' => CommissionsTable::class];
        $this->Commissions = $this->getTableLocator()->get('Commissions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Commissions);

        parent::tearDown();
    }

    /**
     * Every column may stay empty here, so what can go wrong is the validator itself - see the
     * trait.
     *
     * @return void
     * @link \App\Model\Table\CommissionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertValidatorIsNotEmpty($this->Commissions);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\CommissionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Commissions);
    }
}
