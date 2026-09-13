<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The two pages under a customer or a contract, and the window the printed paper opens into.
 *
 * Printing and the papers that came back are read one after the other often enough that going
 * through the card each time is a step for nothing, so each of them offers the other. The card
 * itself is not offered: both say in their own menu how to get back to it.
 */
class PagesAboutOneRecordTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';
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
        'app.ContractVersions',
        'app.ContractProposals',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.Emails',
        'app.Labels',
        'app.CustomerLabels',
        'app.Logins',
        'app.Phones',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.TaskCollaborators',
        'app.DealerCommissions',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

    /**
     * Where the record is read, for each of the two agendas that print.
     *
     * @return array<string, array{string}>
     */
    public static function records(): array
    {
        return [
            'customer' => ['/customers/' . self::CUSTOMER_ID],
            'contract' => [
                '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID,
            ],
        ];
    }

    /**
     * @param string $nested Where the record is read.
     * @return void
     */
    #[DataProvider('records')]
    public function testPrintingAndThePapersOfferEachOther(string $nested): void
    {
        $this->login();

        $this->get($nested . '/print');
        $this->assertResponseOk();
        $this->assertResponseContains(
            $this->button($nested . '/documents'),
            $nested . '/print does not lead to the papers.',
        );

        $this->get($nested . '/documents');
        $this->assertResponseOk();
        $this->assertResponseContains(
            $this->button($nested . '/print'),
            $nested . '/documents does not lead to printing.',
        );
    }

    /**
     * The paper opens in a window of its own, and nothing else does.
     *
     * The page where printing is set up used to be opened into a named window, so that an error
     * sending it back would land in the one window rather than in a new one each time. What can
     * be asked for there is now settled by the proposal, so the page is an ordinary one again and
     * only the document it hands over wants a window.
     *
     * @return void
     */
    public function testOnlyTheDocumentItselfOpensInAWindowOfItsOwn(): void
    {
        foreach (['Contracts', 'Customers'] as $agenda) {
            $template = file_get_contents(ROOT . DS . 'templates' . DS . $agenda . DS . 'print.php');

            $this->assertStringContainsString(
                "'formtarget' => '_blank'",
                (string)$template,
                $agenda . ' hands the document over into the page printing was set up on.',
            );
        }

        $offenders = [];
        foreach ($this->templates() as $template) {
            if (str_contains((string)file_get_contents($template), "'target' => 'print'")) {
                $offenders[] = $template;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Setting printing up is a page like any other and is not opened into a window.',
        );
    }

    /**
     * Every template of the application and of the plugins it carries.
     *
     * @return list<string>
     */
    private function templates(): array
    {
        $found = [];

        foreach ([ROOT . DS . 'templates', ROOT . DS . 'plugins'] as $where) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($where));
            foreach ($files as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                    $found[] = $file->getPathname();
                }
            }
        }

        sort($found);

        return $found;
    }

    /**
     * Anything less than the whole button would be met by the links down the side, which are a
     * different offer and say nothing about what sits above the heading.
     *
     * @param string $address Where it leads.
     * @return string
     */
    private function button(string $address): string
    {
        return '<a href="' . $address . '" class="button float-right';
    }
}
