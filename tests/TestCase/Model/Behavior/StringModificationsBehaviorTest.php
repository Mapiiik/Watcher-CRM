<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Model\Behavior\StringModificationsBehavior;
use App\Model\Table\LabelsTable;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Behavior\StringModificationsBehavior Test Case
 *
 * The behavior tidies up incoming strings before they are marshalled, so it is exercised through a
 * table that carries it rather than by calling the callback directly - going through a table is the
 * only way it ever runs in the application.
 */
#[UsesClass(StringModificationsBehavior::class)]
class StringModificationsBehaviorTest extends TestCase
{
    /**
     * Table carrying the behavior
     *
     * @var \App\Model\Table\LabelsTable
     */
    protected LabelsTable $Labels;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.Labels',
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

        /** @var \App\Model\Table\LabelsTable $labels */
        $labels = $this->getTableLocator()->get('Labels');
        $this->Labels = $labels;
    }

    /**
     * Surrounding whitespace is dropped, so a pasted value does not end up merely looking like the
     * one already stored.
     *
     * @return void
     * @link \App\Model\Behavior\StringModificationsBehavior::beforeMarshal()
     */
    public function testBeforeMarshalTrimsStrings(): void
    {
        $label = $this->Labels->newEntity(['name' => "  Lorem ipsum \n", 'dynamic' => false]);

        $this->assertSame('Lorem ipsum', $label->name);
    }

    /**
     * A field left blank means it is not filled in, not that it holds an empty string - otherwise
     * two ways of saying the same thing both end up in the column.
     *
     * @return void
     * @link \App\Model\Behavior\StringModificationsBehavior::beforeMarshal()
     */
    public function testBeforeMarshalTurnsBlanksIntoNull(): void
    {
        $label = $this->Labels->newEntity(['name' => '', 'caption' => '   ', 'dynamic' => false]);

        $this->assertNull($label->name);
        $this->assertNull($label->caption);
    }

    /**
     * The en dash is what a word processor makes of a typed hyphen, so it arrives whenever a value
     * was written there first. It is replaced, or the same name stops matching itself.
     *
     * @return void
     * @link \App\Model\Behavior\StringModificationsBehavior::beforeMarshal()
     */
    public function testBeforeMarshalReplacesTheEnDash(): void
    {
        $label = $this->Labels->newEntity(['name' => 'Lorem – ipsum', 'dynamic' => false]);

        $this->assertSame('Lorem - ipsum', $label->name);
    }

    /**
     * Only strings are touched; anything else is passed on as it came.
     *
     * @return void
     * @link \App\Model\Behavior\StringModificationsBehavior::beforeMarshal()
     */
    public function testBeforeMarshalLeavesOtherTypesAlone(): void
    {
        $label = $this->Labels->newEntity(['name' => 'Lorem', 'validity' => 7, 'dynamic' => true]);

        $this->assertSame(7, $label->validity);
        $this->assertTrue($label->dynamic);
    }

    /**
     * The tidying happens on the way in rather than on the way to the database, so what is read
     * back is what a later comparison sees.
     *
     * @return void
     * @link \App\Model\Behavior\StringModificationsBehavior::beforeMarshal()
     */
    public function testBeforeMarshalAppliesToSavedRecords(): void
    {
        $label = $this->Labels->newEntity(['name' => '  Lorem – ipsum  ', 'dynamic' => false]);
        $this->Labels->saveOrFail($label);

        $this->assertSame('Lorem - ipsum', $this->Labels->get($label->id)->name);
    }
}
