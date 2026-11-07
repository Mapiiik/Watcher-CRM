<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Billing;
use App\Model\Entity\Service;
use App\Test\Traits\ConfigureTestTrait;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

/**
 * App\Model\Entity\Billing Test Case
 *
 * What a line of a contract comes to is what goes on the invoice, so the amounts here are worked
 * out by hand.
 */
#[CoversClass(Billing::class)]
class BillingTest extends TestCase
{
    use ConfigureTestTrait;

    /**
     * The rate as the database gives it back, a 32-bit float.
     *
     * @var float
     */
    private const STORED_RATE = 0.20999999344348907;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withConfigure(['Billings.roundingPlaces' => 2, 'Billings.roundingType' => 'HALF_UP']);
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * A price of the line goes before the price of the service, and quantity multiplies either.
     *
     * @return void
     * @link \App\Model\Entity\Billing::_getSum()
     */
    public function testThePriceOfTheLineGoesBeforeTheService(): void
    {
        $service = new Service(['price' => Decimal::create('50')]);

        $this->assertAmount('300', $this->billing('100', quantity: 3, service: $service)->sum);
        $this->assertAmount('100', $this->billing(null, quantity: 2, service: $service)->sum);
        $this->assertAmount('0', $this->billing(null)->sum);
    }

    /**
     * Discounts are taken from the sum, the percentage of the whole sum, not of what the fixed one
     * left.
     *
     * @return void
     * @link \App\Model\Entity\Billing::_getTotalPrice()
     */
    public function testBothDiscountsComeOffTheSum(): void
    {
        $billing = $this->billing('100', quantity: 3, fixedDiscount: '10', percentageDiscount: 25);

        $this->assertAmount('10', $billing->fixed_discount_sum);
        $this->assertAmount('75', $billing->percentage_discount_sum);
        $this->assertAmount('85', $billing->discount);
        $this->assertAmount('215', $billing->total_price);
    }

    /**
     * A percentage is rounded to hellers before it comes off.
     *
     * @return void
     * @link \App\Model\Entity\Billing::_getPercentageDiscountSum()
     */
    public function testAPercentageIsRoundedToHellers(): void
    {
        $billing = $this->billing('33.33', percentageDiscount: 15);

        $this->assertAmount('5', $billing->percentage_discount_sum);
        $this->assertAmount('28.33', $billing->total_price);
    }

    /**
     * The name is the text of the line or of the service, with the count where there is more than one.
     *
     * @return void
     * @link \App\Model\Entity\Billing::_getName()
     */
    public function testTheNameSaysHowMany(): void
    {
        $service = new Service(['name' => 'Internet']);

        $this->assertSame('Internet', $this->billing('1', service: $service)->name);
        $this->assertSame('3x Internet', $this->billing('1', quantity: 3, service: $service)->name);
        $this->assertSame('Own text', (new Billing(['text' => 'Own text', 'service' => $service, 'quantity' => 1]))->name);
    }

    /**
     * Part of a month is paid for by its days, both ends included. June has 30 days, so at 300 a
     * month a day comes to 10.
     *
     * @param string|null $from First day billed.
     * @param string|null $until Last day billed.
     * @param string $expected What June comes to.
     * @return void
     * @link \App\Model\Entity\Billing::periodTotal()
     */
    #[DataProvider('partsOfJune')]
    public function testPartOfAMonthIsPaidForByItsDays(?string $from, ?string $until, string $expected): void
    {
        $billing = $this->billing('300', from: $from, until: $until);

        $this->assertAmount($expected, $billing->periodTotal(new Date('2026-06-01'), new Date('2026-06-30')));
    }

    /**
     * @return array<string, array{string|null, string|null, string}>
     */
    public static function partsOfJune(): array
    {
        return [
            'running through' => ['2020-01-01', null, '300'],
            'exactly the month' => ['2026-06-01', '2026-06-30', '300'],
            'ending later' => ['2020-01-01', '2026-09-30', '300'],
            'starting within' => ['2026-06-16', null, '150'],
            'starting on the last day' => ['2026-06-30', null, '10'],
            'ending within' => ['2020-01-01', '2026-06-10', '100'],
            'ending on the first day' => ['2020-01-01', '2026-06-01', '10'],
            'starting and ending within' => ['2026-06-11', '2026-06-20', '100'],
            'one day' => ['2026-06-15', '2026-06-15', '10'],
            'starting later' => ['2026-07-01', null, '0'],
            'over before' => ['2020-01-01', '2026-05-31', '0'],
            'never started' => [null, null, '0'],
        ];
    }

    /**
     * The days are counted by the month in question: July has 31 of them.
     *
     * @return void
     * @link \App\Model\Entity\Billing::periodTotal()
     */
    public function testALongerMonthHasCheaperDays(): void
    {
        $billing = $this->billing('1000', from: '2026-07-01', until: '2026-07-10');

        $this->assertAmount('322.58', $billing->periodTotal(new Date('2026-07-01'), new Date('2026-07-31')));
    }

    /**
     * What is credited back is rounded the same way as what is charged, away from nought.
     *
     * @return void
     * @link \App\Model\Entity\Billing::periodTotal()
     */
    public function testACreditIsRoundedLikeACharge(): void
    {
        $billing = $this->billing('-1000', from: '2026-07-01', until: '2026-07-10');

        $this->assertAmount('-322.58', $billing->periodTotal(new Date('2026-07-01'), new Date('2026-07-31')));
    }

    /**
     * The rounding of a part of a month is the operator's choice. 1000 for 10 days of July is
     * 322.58 and for 20 days 645.16.
     *
     * @param string $type The rounding.
     * @param string $tenDays What ten days come to.
     * @param string $twentyDays What twenty days come to.
     * @return void
     * @link \App\Model\Entity\Billing::roundForBillingPeriod()
     */
    #[TestWith(['HALF_UP', '323', '645'])]
    #[TestWith(['CEIL', '323', '646'])]
    #[TestWith(['FLOOR', '322', '645'])]
    public function testTheRoundingOfAMonthIsConfigured(string $type, string $tenDays, string $twentyDays): void
    {
        Configure::write('Billings.roundingPlaces', 0);
        Configure::write('Billings.roundingType', $type);
        $july = [new Date('2026-07-01'), new Date('2026-07-31')];

        $this->assertAmount($tenDays, $this->billing('1000', from: '2026-07-01', until: '2026-07-10')->periodTotal(...$july));
        $this->assertAmount($twentyDays, $this->billing('1000', from: '2026-07-01', until: '2026-07-20')->periodTotal(...$july));
    }

    /**
     * Rounding down and up go by the number line, not by the distance from nought.
     *
     * @param string $type The rounding.
     * @param string $expected What -1.1 comes to.
     * @return void
     * @link \App\Model\Entity\Billing::roundForBillingPeriod()
     */
    #[TestWith(['HALF_UP', '-1'])]
    #[TestWith(['CEIL', '-1'])]
    #[TestWith(['FLOOR', '-2'])]
    public function testRoundingANegativeAmount(string $type, string $expected): void
    {
        Configure::write('Billings.roundingPlaces', 0);
        Configure::write('Billings.roundingType', $type);

        $this->assertAmount($expected, Billing::roundForBillingPeriod(Decimal::create('-1.1')));
    }

    /**
     * A rounding nobody knows stops the run instead of invoicing something unrounded.
     *
     * @return void
     * @link \App\Model\Entity\Billing::roundForBillingPeriod()
     */
    public function testAnUnknownRoundingIsRefused(): void
    {
        Configure::write('Billings.roundingType', 'SOMEHOW');

        $this->expectException(RuntimeException::class);
        Billing::roundForBillingPeriod(Decimal::create('1'));
    }

    /**
     * VAT is worked out from a price that includes it, and the two parts add up to the price.
     *
     * @param string $total The price with VAT.
     * @param string $base What is left without it.
     * @param string $vat The VAT.
     * @return void
     * @link \App\Model\Entity\Billing::calcVatFromTotal()
     */
    #[TestWith(['121', '100', '21'])]
    #[TestWith(['100', '82.64', '17.36'])]
    #[TestWith(['3630', '3000', '630'])]
    #[TestWith(['0.01', '0.01', '0'])]
    public function testVatIsTakenOutOfThePrice(string $total, string $base, string $vat): void
    {
        foreach ([0.21, self::STORED_RATE] as $rate) {
            $this->assertAmount($vat, Billing::calcVatFromTotal(Decimal::create($total), $rate));
            $this->assertAmount($base, Billing::calcVatBaseFromTotal(Decimal::create($total), $rate));
        }
    }

    /**
     * A line.
     *
     * @param string|null $price The price of the line.
     * @param int $quantity How many.
     * @param \App\Model\Entity\Service|null $service The service.
     * @param string|null $fixedDiscount The fixed discount.
     * @param int|null $percentageDiscount The percentage discount.
     * @param string|null $from First day billed.
     * @param string|null $until Last day billed.
     * @return \App\Model\Entity\Billing
     */
    private function billing(
        ?string $price,
        int $quantity = 1,
        ?Service $service = null,
        ?string $fixedDiscount = null,
        ?int $percentageDiscount = null,
        ?string $from = null,
        ?string $until = null,
    ): Billing {
        return new Billing(array_filter([
            'price' => $price === null ? null : Decimal::create($price),
            'quantity' => $quantity,
            'service' => $service,
            'fixed_discount' => $fixedDiscount === null ? null : Decimal::create($fixedDiscount),
            'percentage_discount' => $percentageDiscount,
            'billing_from' => $from === null ? null : new Date($from),
            'billing_until' => $until === null ? null : new Date($until),
        ], fn(mixed $value): bool => $value !== null));
    }

    /**
     * @param string $expected The amount.
     * @param \PhpCollective\DecimalObject\Decimal $actual What came out.
     * @return void
     */
    private function assertAmount(string $expected, Decimal $actual): void
    {
        $this->assertTrue($actual->equals(Decimal::create($expected)), $expected . ' is not ' . $actual);
    }
}
