<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Cell;

use App\Test\Traits\ControllerTestTrait;
use App\View\Cell\CustomerCell;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\View\Cell\CustomerCell Test Case
 *
 * The cell is the header the layout puts on every page reached under a customer: their name, and a
 * button per contract. Its links go through `AuthLink`, which asks the authorization service in the
 * request whether to render each one, so the cell is exercised through a request rather than on its
 * own - outside one it has nothing to ask.
 */
#[UsesClass(CustomerCell::class)]
class CustomerCellTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer owning the contract from the Contracts fixture.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract from the Contracts fixture.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.Emails',
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
    }

    /**
     * A page reached under a customer is headed by them, with their contracts to move on to.
     *
     * @return void
     * @link \App\View\Cell\CustomerCell::display()
     */
    public function testDisplay(): void
    {
        $this->get('/customers/' . self::CUSTOMER_ID . '/emails');

        $this->assertResponseOk();
        $this->assertResponseContains('/customers/' . self::CUSTOMER_ID . '"');
        $this->assertResponseContains(self::CONTRACT_ID);
    }

    /**
     * In the popup window the header goes compact and drops the contracts the reader is not on -
     * there is nowhere to navigate to from a window opened on one thing.
     *
     * @return void
     * @link \App\View\Cell\CustomerCell::display()
     */
    public function testDisplayCompactLeavesOutTheOtherContracts(): void
    {
        $this->get('/customers/' . self::CUSTOMER_ID . '/emails?win-link=true');

        $this->assertResponseOk();
        $this->assertResponseContains('/customers/' . self::CUSTOMER_ID . '"');
        $this->assertResponseNotContains('/contracts/' . self::CONTRACT_ID);
    }

    /**
     * A page that is not under a customer gets no header, rather than one for nobody.
     *
     * @return void
     * @link \App\View\Cell\CustomerCell::display()
     */
    public function testDisplayIsLeftOutWithoutACustomer(): void
    {
        $this->get('/emails');

        $this->assertResponseOk();
        $this->assertResponseNotContains('nav-content top-nav');
    }
}
