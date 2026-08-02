<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Model\Behavior\FootprintBehavior;
use App\Model\Table\LabelsTable;
use App\Test\Traits\IdentityColumnTrait;
use ArrayObject;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Behavior\FootprintBehavior Test Case
 *
 * The behavior stamps who created and who last changed a record, taking the identity from the
 * current request. It is therefore exercised through a table that carries it, with a request in
 * place - that is the only way it ever runs in the application.
 */
#[UsesClass(FootprintBehavior::class)]
class FootprintBehaviorTest extends TestCase
{
    use IdentityColumnTrait;

    /**
     * The user from the AppUsers fixture, standing in for whoever is logged in.
     *
     * @var string
     */
    private const USER_ID = '11edb519-be76-4d66-aea0-34188d31eae1';

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
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Router::reload();

        parent::tearDown();
    }

    /**
     * Put a request carrying the given identity in place, the way the authentication middleware
     * would have done by the time a table gets saved.
     *
     * @param string|null $userId Identity to act as, or null for a request nobody is behind.
     * @return void
     */
    private function requestAs(?string $userId): void
    {
        $request = new ServerRequest();
        if ($userId !== null) {
            $request = $request->withAttribute('identity', new ArrayObject(['id' => $userId]));
        }

        Router::setRequest($request);
    }

    /**
     * A new record is stamped with its author on both columns - whoever created it is also the last
     * to have changed it.
     *
     * @return void
     * @link \App\Model\Behavior\FootprintBehavior::beforeSave()
     */
    public function testBeforeSaveStampsANewRecord(): void
    {
        $this->requestAs(self::USER_ID);

        $label = $this->Labels->newEntity(['name' => 'Lorem', 'dynamic' => false]);
        $this->Labels->saveOrFail($label);

        $this->assertSame(self::USER_ID, $label->created_by);
        $this->assertSame(self::USER_ID, $label->modified_by);
    }

    /**
     * Changing a record leaves its author alone. Who wrote it down in the first place is a
     * different question from who touched it last, and the answer must not drift.
     *
     * @return void
     * @link \App\Model\Behavior\FootprintBehavior::beforeSave()
     */
    public function testBeforeSaveLeavesTheAuthorOfAnExistingRecord(): void
    {
        // a second user to tell the two columns apart; the password goes in past its setter, which
        // reaches for a hasher the test environment does not configure
        $this->advanceIdentity('users', 'nid');
        $users = $this->getTableLocator()->get('AppUsers');
        $creator = $users->newEmptyEntity();
        $creator->patch(['username' => 'author', 'role' => 'admin', 'active' => true]);
        $creator->set('password', 'irrelevant-for-this-test', ['setter' => false]);
        $users->saveOrFail($creator);

        $this->requestAs($creator->id);
        $label = $this->Labels->newEntity(['name' => 'Lorem', 'dynamic' => false]);
        $this->Labels->saveOrFail($label);

        $this->requestAs(self::USER_ID);
        $this->Labels->saveOrFail($this->Labels->patchEntity($label, ['name' => 'Ipsum']));

        $reloaded = $this->Labels->get($label->id);
        $this->assertSame($creator->id, $reloaded->created_by);
        $this->assertSame(self::USER_ID, $reloaded->modified_by);
    }

    /**
     * Without an identity there is nobody to name, and the record is saved unstamped rather than
     * refused - the console runs without one.
     *
     * @return void
     * @link \App\Model\Behavior\FootprintBehavior::beforeSave()
     */
    public function testBeforeSaveLeavesTheColumnsAloneWithoutAnIdentity(): void
    {
        $this->requestAs(null);

        $label = $this->Labels->newEntity(['name' => 'Lorem', 'dynamic' => false]);
        $this->Labels->saveOrFail($label);

        $this->assertNull($label->created_by);
        $this->assertNull($label->modified_by);
    }
}
