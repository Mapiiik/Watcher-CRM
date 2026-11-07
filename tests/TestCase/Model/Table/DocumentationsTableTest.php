<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\DocumentationsTable;
use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Table\DocumentationsTable Test Case
 *
 * What the application adds to the shared folder. The folder itself is tested in the plugin.
 */
#[CoversClass(DocumentationsTable::class)]
class DocumentationsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\DocumentationsTable
     */
    protected $Documentations;

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
        'plugin.Files.DocumentationTypes',
        'plugin.Files.Documentations',
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
        $config = $this->getTableLocator()->exists('Documentations')
            ? []
            : ['className' => DocumentationsTable::class];
        $this->Documentations = $this->getTableLocator()->get('Documentations', $config);
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
        unset($this->Documentations);

        parent::tearDown();
    }

    /**
     * What a folder hangs on is there, in the columns the application added.
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertAssociationsMatchTheSchema($this->Documentations);
    }

    /**
     * A folder cannot hang on a customer or a contract that is not there.
     *
     * @return void
     * @link \App\Model\Table\DocumentationsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $types = $this->fetchTable('DocumentationTypes');
        $kind = $types->saveOrFail($types->newEntity([
            'name' => 'Documentation',
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
        ]));
        $this->Documentations->saveOrFail($this->Documentations->newEntity([
            'documentation_type_id' => $kind->id,
            'name' => 'The wiring in the rack',
        ]));

        $this->assertDanglingReferencesAreRefused($this->Documentations);
    }
}
