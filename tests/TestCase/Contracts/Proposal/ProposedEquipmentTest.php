<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposedEquipment;
use App\Model\Enum\EquipmentMove;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Proposal\ProposedEquipment Test Case
 */
#[CoversClass(ProposedEquipment::class)]
class ProposedEquipmentTest extends TestCase
{
    /**
     * A line reads back as it was written, the kind it lends included.
     *
     * @return void
     */
    public function testALineReadsBackAsItWasWritten(): void
    {
        $line = new ProposedEquipment(
            id: 'line-1',
            move: EquipmentMove::Lend,
            borrowed_equipment_id: null,
            equipment_type_id: 'type-1',
            serial_number: 'SN-1',
            equipment_type: ['id' => 'type-1', 'name' => 'Box', 'price' => '1500.00'],
            note: 'On the roof',
        );

        $this->assertEquals($line, ProposedEquipment::fromArray($line->toArray()));
        $this->assertTrue($line->lends());
        $this->assertFalse($line->takesBack());
    }

    /**
     * A return has to say what goes back and a loan what kind is lent, or there is nothing to write.
     *
     * @return void
     */
    public function testALineSaysWhatItIsAbout(): void
    {
        foreach (
            [
                ['move' => 'return'],
                ['move' => 'lend'],
                ['move' => 'swap', 'equipment_type_id' => 'type-1'],
            ] as $stored
        ) {
            try {
                ProposedEquipment::fromArray($stored);
                $this->fail('A line saying nothing about what it moves was read.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * The changes keep the equipment lines beside the billing ones, and answer which way it goes.
     *
     * @return void
     */
    public function testTheChangesKeepTheEquipment(): void
    {
        $stored = [
            ProposalChanges::EQUIPMENTS => [
                ['id' => 'back', 'move' => 'return', 'borrowed_equipment_id' => 'old-1'],
                ['id' => 'new', 'move' => 'lend', 'equipment_type_id' => 'type-1'],
            ],
        ];

        $changes = ProposalChanges::fromArray($stored);

        $this->assertFalse($changes->isEmpty());
        $this->assertTrue($changes->exchangesEquipment());
        $this->assertSame(['old-1'], array_keys($changes->equipmentTakenBack()));
        $this->assertSame('new', $changes->equipmentLent()[0]->id);
        $this->assertSame('back', $changes->equipmentLine('back')?->id);

        $withoutTheReturn = $changes->withoutEquipmentLine('back');
        $this->assertFalse($withoutTheReturn->exchangesEquipment());
        $this->assertSame([], ProposalChanges::fromArray($withoutTheReturn->withoutEquipmentLine('new')->toArray())
            ->equipments);

        // what goes in comes back out the same, and a billing line leaves the equipment alone
        $this->assertSame($changes->toArray(), ProposalChanges::fromArray($changes->toArray())->toArray());
        $this->assertCount(2, $changes->withVersion($changes->version)->equipments);
    }
}
