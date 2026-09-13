<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Files\Model\Entity\DocumentationType;
use Files\Model\Table\DocumentationsTable;
use Files\Model\Table\DocumentationTypesTable;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Model\Table\DocumentationTypesTable Test Case
 *
 * The list somebody picks a kind of folder from. What matters about it is that it can be changed
 * without what is already filed changing underneath.
 */
#[UsesClass(DocumentationTypesTable::class)]
class DocumentationTypesTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Files.DocumentationTypes',
        'plugin.Files.Documentations',
    ];

    /**
     * @var \Files\Model\Table\DocumentationTypesTable
     */
    private DocumentationTypesTable $DocumentationTypes;

    /**
     * @var \Files\Model\Table\DocumentationsTable
     */
    private DocumentationsTable $Documentations;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Files\Model\Table\DocumentationTypesTable $types */
        $types = $this->fetchTable('Files.DocumentationTypes');
        $this->DocumentationTypes = $types;

        /** @var \Files\Model\Table\DocumentationsTable $documentations */
        $documentations = $this->fetchTable('Files.Documentations');
        $this->Documentations = $documentations;
    }

    /**
     * Taking a kind off the list is how it is retired. Deleting it would leave the folders under
     * it saying nothing about what they are.
     *
     * @link \Files\Model\Table\DocumentationTypesTable::buildRules()
     * @return void
     */
    public function testAKindWithFoldersUnderItIsNotDeleted(): void
    {
        $kind = $this->kind();

        $this->Documentations->saveOrFail($this->Documentations->newEntity([
            'documentation_type_id' => $kind->id,
            'name' => 'The wiring in the rack',
        ]));

        $this->assertFalse($this->DocumentationTypes->delete($kind));
    }

    /**
     * @link \Files\Model\Table\DocumentationTypesTable::buildRules()
     * @return void
     */
    public function testAKindNobodyHasUsedGoesWhenItIsDeleted(): void
    {
        $this->assertTrue($this->DocumentationTypes->delete($this->kind()));
    }

    /**
     * @link \Files\Model\Table\DocumentationTypesTable::findOffered()
     * @return void
     */
    public function testOnlyWhatIsOfferedIsOffered(): void
    {
        $this->kind(['name' => 'Installation', 'position' => 1]);
        $retired = $this->kind([
            'name' => 'Something we stopped doing',
            'position' => 2,
            'currently_offered' => false,
        ]);

        $this->assertSame(['Installation'], $this->offered());
        $this->assertSame(
            ['Installation', 'Something we stopped doing'],
            $this->offered((string)$retired->id),
            'The kind a folder already carries stays on the list while that folder is edited.',
        );
    }

    /**
     * The names on offer, in the order they read.
     *
     * @param string|null $including A kind to keep whatever it says about itself.
     * @return list<string>
     */
    private function offered(?string $including = null): array
    {
        /** @var list<\Files\Model\Entity\DocumentationType> $types */
        $types = $this->DocumentationTypes->find('offered', including: $including)->all()->toList();

        return array_map(fn(DocumentationType $type): string => (string)$type->name, $types);
    }

    /**
     * A kind of folder, saved.
     *
     * @param array<string, mixed> $said What to say about it.
     * @return \Files\Model\Entity\DocumentationType
     */
    private function kind(array $said = []): DocumentationType
    {
        return $this->DocumentationTypes->saveOrFail($this->DocumentationTypes->newEntity($said + [
            'name' => 'Documentation',
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
        ]));
    }
}
