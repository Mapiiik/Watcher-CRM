<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts;

use App\Contracts\MinimumConnectionPrice;
use App\Model\Entity\Billing;
use App\Model\Entity\Queue;
use App\Model\Entity\Service;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\MinimumConnectionPrice Test Case
 */
#[CoversClass(MinimumConnectionPrice::class)]
class MinimumConnectionPriceTest extends TestCase
{
    /**
     * A service read from the table says so by its queue id, one kept by a proposal by the queue.
     *
     * @return void
     */
    public function testTheConnectionIsTheServiceWithAQueue(): void
    {
        $this->assertTrue(MinimumConnectionPrice::isConnection($this->billing(new Service(['queue_id' => 1]))));
        $this->assertTrue(
            MinimumConnectionPrice::isConnection($this->billing(new Service(['queue' => new Queue(['id' => 1])]))),
        );
        $this->assertFalse(MinimumConnectionPrice::isConnection($this->billing(new Service(['queue_id' => null]))));
        $this->assertFalse(MinimumConnectionPrice::isConnection($this->billing(null)));
    }

    /**
     * What is measured is the price after discounts, the list price where the billing has none.
     *
     * @return void
     */
    public function testThePriceAfterDiscountsIsMeasured(): void
    {
        $minimum = Decimal::create('100');
        $connection = new Service(['queue_id' => 1, 'price' => Decimal::create('100')]);

        $this->assertFalse(MinimumConnectionPrice::fallsBelow($this->billing($connection), $minimum));
        $this->assertTrue(MinimumConnectionPrice::fallsBelow(
            $this->billing($connection, ['percentage_discount' => 10]),
            $minimum,
        ));
        $this->assertTrue(MinimumConnectionPrice::fallsBelow(
            $this->billing($connection, ['price' => Decimal::create('99.99')]),
            $minimum,
        ));
    }

    /**
     * Without a minimum, or beside the connection, nothing falls below anything.
     *
     * @return void
     */
    public function testNothingFallsBelowWhereThereIsNothingToMeasure(): void
    {
        $cheap = ['price' => Decimal::create('1')];

        $this->assertFalse(MinimumConnectionPrice::fallsBelow(
            $this->billing(new Service(['queue_id' => 1]), $cheap),
            null,
        ));
        $this->assertFalse(MinimumConnectionPrice::fallsBelow(
            $this->billing(new Service(['queue_id' => null]), $cheap),
            Decimal::create('100'),
        ));
    }

    /**
     * A billing of one, for the given service.
     *
     * @param \App\Model\Entity\Service|null $service The service.
     * @param array<string, mixed> $terms Anything else it says.
     * @return \App\Model\Entity\Billing
     */
    private function billing(?Service $service, array $terms = []): Billing
    {
        return new Billing($terms + ['quantity' => 1, 'service' => $service]);
    }
}
