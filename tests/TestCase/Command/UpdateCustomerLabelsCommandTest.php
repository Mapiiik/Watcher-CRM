<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\UpdateCustomerLabelsCommand;
use App\Test\Traits\ConfigureTestTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\UpdateCustomerLabelsCommand Test Case
 *
 * A dynamic label is a query somebody wrote in the application, and this run is what turns it into
 * the labels a customer is actually seen to carry. The query is the deployment's, so the tests write
 * their own rather than lean on whatever the fixture happens to hold.
 */
#[UsesClass(UpdateCustomerLabelsCommand::class)]
class UpdateCustomerLabelsCommandTest extends TestCase
{
    use ConfigureTestTrait;
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

    /**
     * The customer the fixtures carry.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

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
        'app.Commissions',
        'app.Labels',
        'app.CustomerLabels',
    ];

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * Write a dynamic label that selects whatever the given query selects.
     *
     * @param string $sql The query it is to run.
     * @return string Id of the label.
     */
    private function dynamicLabel(string $sql): string
    {
        $labels = $this->getTableLocator()->get('Labels');
        $label = $labels->saveOrFail($labels->newEntity([
            'name' => 'Dynamic label',
            'dynamic' => true,
            'dynamic_sql' => $sql,
        ]));

        return (string)$label->get('id');
    }

    /**
     * The arguments a cron entry would name are there.
     *
     * @return void
     * @link \App\Command\UpdateCustomerLabelsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('update_customer_labels --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('label_id');
    }

    /**
     * The customers a label's query selects come to carry it.
     *
     * @return void
     * @link \App\Command\UpdateCustomerLabelsCommand::execute()
     */
    public function testExecuteGivesTheLabelToWhomItsQuerySelects(): void
    {
        $label = $this->dynamicLabel(
            "SELECT id AS customer_id FROM customers WHERE id = '" . self::CUSTOMER_ID . "'",
        );

        $this->exec('update_customer_labels ' . $label);

        $this->assertExitSuccess();
        $this->assertTrue(
            $this->getTableLocator()->get('CustomerLabels')->exists([
                'label_id' => $label,
                'customer_id' => self::CUSTOMER_ID,
            ]),
        );
    }

    /**
     * Named a label, the run leaves the others alone - which is what makes it safe to fix one
     * label's query and try it without waiting for every other query to run again.
     *
     * @return void
     * @link \App\Command\UpdateCustomerLabelsCommand::execute()
     */
    public function testExecuteWorksOnlyOnTheLabelNamed(): void
    {
        $label = $this->dynamicLabel(
            "SELECT id AS customer_id FROM customers WHERE id = '" . self::CUSTOMER_ID . "'",
        );
        $other = $this->dynamicLabel(
            "SELECT id AS customer_id FROM customers WHERE id = '" . self::CUSTOMER_ID . "'",
        );

        $this->exec('update_customer_labels ' . $label);

        $this->assertExitSuccess();
        $this->assertFalse(
            $this->getTableLocator()->get('CustomerLabels')->exists(['label_id' => $other]),
        );
    }

    /**
     * A query that will not run stops the run and says which label it was.
     *
     * Carrying on would take the label to have selected nobody, and a label nobody carries reads
     * the same whether the query said so or could not be asked. Whoever is configured is told,
     * a nightly run that fails quietly being the one that goes unnoticed longest.
     *
     * @return void
     * @link \App\Command\UpdateCustomerLabelsCommand::execute()
     */
    public function testExecuteStopsOnALabelWhoseQueryWillNotRun(): void
    {
        $this->withConfigure(['Report.emails' => ['labels@example.com']]);
        $label = $this->dynamicLabel('SELECT this is not a query');

        $this->exec('update_customer_labels ' . $label);

        $this->assertExitError();
        $this->assertErrorContains($label);
        $this->assertMailSentTo('labels@example.com');
    }
}
