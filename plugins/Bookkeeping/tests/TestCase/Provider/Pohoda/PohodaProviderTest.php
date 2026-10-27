<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Provider\Pohoda;

use App\Model\Entity\Customer;
use Bookkeeping\Provider\Pohoda\PohodaProvider;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Settings\Utility\Settings;

/**
 * Bookkeeping\Provider\Pohoda\PohodaProvider Test Case
 *
 * Which partner cards are ours to write. mServer is made up to refuse whoever it is asked about, so
 * a customer who is sent ends in a refusal, and one left out ends in nothing at all.
 */
#[CoversClass(PohodaProvider::class)]
class PohodaProviderTest extends TestCase
{
    use HttpClientTrait;

    private const URL = 'http://mserver.invalid';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Settings.Settings',
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

        Cache::clear('default');
        Configure::write('Customers.series', 117000);
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.api.url', self::URL);

        $this->mockClientPost(
            self::URL . '/xml',
            $this->newClientResponse(
                200,
                ['Content-Type: application/xml'],
                '<?xml version="1.0" encoding="UTF-8"?>'
                . '<rsp:responsePack version="2.0" id="adb" state="ok"'
                . ' xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd">'
                . '<rsp:responsePackItem version="2.0" id="117512" state="error" note="Refused"/>'
                . '</rsp:responsePack>',
            ),
        );
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * Customer number 117512, synchronized as given.
     *
     * @param bool $syncToAccounting Whether the customer is to be synchronized.
     * @return \App\Model\Entity\Customer
     */
    private function customer(bool $syncToAccounting = true): Customer
    {
        return new Customer([
            'nid' => 512,
            'sync_to_accounting' => $syncToAccounting,
            'addresses' => [],
            'emails' => [],
            'phones' => [],
        ]);
    }

    /**
     * @return array<string, array{bool, int, bool}>
     */
    public static function partnerCards(): array
    {
        return [
            'everybody' => [true, 0, true],
            'from their number on' => [true, 117512, true],
            'from a later number on' => [true, 117513, false],
            'not to be synchronized' => [false, 0, false],
        ];
    }

    /**
     * The card is ours where the customer is to be synchronized and their number is not below the
     * one the cards are ours from.
     *
     * @param bool $syncToAccounting Whether the customer is to be synchronized.
     * @param int $minCustomerNumber The number the cards are ours from.
     * @param bool $ours Whether the card is ours.
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\PohodaProvider::writesPartnerCardOf()
     */
    #[DataProvider('partnerCards')]
    public function testWhichPartnerCardsAreOurs(bool $syncToAccounting, int $minCustomerNumber, bool $ours): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.min_customer_number', $minCustomerNumber);

        $this->assertSame($minCustomerNumber, PohodaProvider::minCustomerNumber());
        $this->assertSame($ours, PohodaProvider::writesPartnerCardOf($this->customer($syncToAccounting)));
    }

    /**
     * A customer below the number is left out of the run, not sent.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\PohodaProvider::sendPartners()
     */
    public function testACustomerBelowTheFirstNumberIsLeftOut(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.min_customer_number', 117600);

        $this->expectNotToPerformAssertions();

        (new PohodaProvider())->sendPartners([$this->customer()]);
    }

    /**
     * From the number on, the customer is sent.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\PohodaProvider::sendPartners()
     */
    public function testACustomerFromTheFirstNumberIsSent(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.min_customer_number', 117512);

        try {
            (new PohodaProvider())->sendPartners([$this->customer()]);
            $this->fail('The customer was left out.');
        } catch (RuntimeException $e) {
            $this->assertSame('Pohoda refused these customers: 117512 (Refused)', $e->getMessage());
        }
    }
}
