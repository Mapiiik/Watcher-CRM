<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\MissingInstallationDateCheck;
use App\Model\Table\ContractsTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Check\MissingInstallationDateCheck Test Case
 *
 * The installation date is what every other date on the contract is read against, so one
 * without it cannot be checked at all - but only where there was anything to install.
 */
#[UsesClass(MissingInstallationDateCheck::class)]
class MissingInstallationDateCheckTest extends TestCase
{
    use LocatorAwareTrait;

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
    ];

    private ContractsTable $Contracts;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->Contracts = $this->getTableLocator()->get('Contracts', ['className' => ContractsTable::class]);
    }

    /**
     * @return void
     * @link \App\Contracts\Check\MissingInstallationDateCheck::find()
     */
    public function testAnInstalledContractWithNoDateIsReported(): void
    {
        $this->installedOn(null);

        $this->assertCount(1, $this->found());
    }

    /**
     * @return void
     * @link \App\Contracts\Check\MissingInstallationDateCheck::find()
     */
    public function testAnInstalledContractWithADateIsNotReported(): void
    {
        $this->installedOn('2024-03-01');

        $this->assertSame([], $this->found());
    }

    /**
     * Hosting is not put in anywhere, so it has no day it was put in. The flag on the service
     * type is what tells the two apart - which means a service type added later is judged by
     * what it is rather than by a list somebody has to remember to extend.
     *
     * @return void
     * @link \App\Contracts\Check\MissingInstallationDateCheck::find()
     */
    public function testAContractThatIsNotInstalledAnywhereIsNotAsked(): void
    {
        $this->installedOn(null);
        $this->getTableLocator()->get('ServiceTypes')
            ->updateAll(['installation_address_required' => false], ['1 = 1']);

        $this->assertSame([], $this->found());
    }

    /**
     * Run the check and return what it found.
     *
     * @return list<\App\Model\Entity\Contract>
     */
    private function found(): array
    {
        /** @var list<\App\Model\Entity\Contract> $records */
        $records = (new MissingInstallationDateCheck($this->Contracts, true, self::CONTRACT_ID))
            ->find()
            ->all()
            ->toList();

        return $records;
    }

    /**
     * Say when the contract under test was installed, or that nothing says.
     *
     * @param string|null $on The day it was installed, or null for none.
     * @return void
     */
    private function installedOn(?string $on): void
    {
        $this->Contracts->updateAll(['installation_date' => $on], ['id' => self::CONTRACT_ID]);
    }
}
