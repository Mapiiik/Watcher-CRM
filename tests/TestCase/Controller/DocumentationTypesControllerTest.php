<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\DocumentationTypesController;
use App\Model\Entity\DocumentationType;
use App\Model\Table\DocumentationTypesTable;
use App\Test\Traits\ControllerTestTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\DocumentationTypesController Test Case
 *
 * The short list somebody picks a documentation type from. What matters is that it can be changed
 * without what is already filed changing underneath, which is why a type in use is taken off the
 * list rather than deleted.
 */
#[UsesClass(DocumentationTypesController::class)]
class DocumentationTypesControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;
    use LocatorAwareTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
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

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * @link \App\Controller\DocumentationTypesController::index()
     * @return void
     */
    public function testEveryPageOfTheAgendaAnswers(): void
    {
        $kind = $this->kind();

        foreach (['index', 'add', 'view/' . $kind->id, 'edit/' . $kind->id] as $page) {
            $this->get('/documentation-types/' . $page);
            $this->assertResponseOk('The page at ' . $page . ' did not answer.');
        }
    }

    /**
     * @link \App\Controller\DocumentationTypesController::add()
     * @return void
     */
    public function testOneIsAddedWithWhatItRequiresOfWhatIsFiledUnderIt(): void
    {
        $this->post('/documentation-types/add', [
            'name' => 'Installation',
            'position' => 1,
            'currently_offered' => 1,
            'date_required' => 1,
            'customer_required' => 0,
            'contract_required' => 1,
        ]);

        $this->assertRedirect();

        $added = $this->types()->find()->where(['name' => 'Installation'])->firstOrFail();

        $this->assertTrue($added->get('date_required'));
        $this->assertTrue($added->get('contract_required'));
        $this->assertFalse($added->get('customer_required'));
    }

    /**
     * Taking a type off the list is how it is retired. Deleting it would leave what is filed under
     * it saying nothing about what it is.
     *
     * @link \App\Controller\DocumentationTypesController::delete()
     * @return void
     */
    public function testATypeWithDocumentationUnderItIsNotDeleted(): void
    {
        $kind = $this->kind();

        $documentations = $this->fetchTable('Documentations');
        $documentations->saveOrFail($documentations->newEntity([
            'documentation_type_id' => $kind->id,
            'name' => 'The installation',
        ]));

        $this->post('/documentation-types/delete/' . $kind->id);

        $this->assertRedirect();
        $this->assertSame(1, $this->types()->find()->count());
    }

    /**
     * A documentation type, saved.
     *
     * @return \App\Model\Entity\DocumentationType
     */
    private function kind(): DocumentationType
    {
        /** @var \App\Model\Entity\DocumentationType $kind */
        $kind = $this->types()->saveOrFail($this->types()->newEntity([
            'name' => 'Installation',
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
            'customer_required' => false,
            'contract_required' => false,
        ]));

        return $kind;
    }

    /**
     * @return \App\Model\Table\DocumentationTypesTable
     */
    private function types(): DocumentationTypesTable
    {
        /** @var \App\Model\Table\DocumentationTypesTable $table */
        $table = $this->fetchTable('DocumentationTypes');

        return $table;
    }
}
