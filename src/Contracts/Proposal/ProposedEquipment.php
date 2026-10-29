<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Enum\EquipmentMove;
use Cake\Utility\Text;
use InvalidArgumentException;

/**
 * One line of what a proposal asks to happen to the equipment lent with the contract.
 *
 * A line either lends something new or takes back something the snapshot knows. What is lent
 * carries its kind as it stood when it was chosen, because the papers print its name and value
 * and the contract's snapshot has never heard of it.
 */
final class ProposedEquipment
{
    /**
     * @param string $id This line's own name, so that it can be edited and dropped again.
     * @param \App\Model\Enum\EquipmentMove $move Whether it is lent or taken back.
     * @param string|null $borrowed_equipment_id What is taken back; only on a return.
     * @param string|null $equipment_type_id What kind is lent; only on a loan.
     * @param string|null $serial_number Its serial number, where it is known.
     * @param array<string, mixed>|null $equipment_type The kind as it stood when it was chosen.
     * @param string|null $note Whatever the operator wrote down.
     */
    public function __construct(
        public readonly string $id,
        public readonly EquipmentMove $move,
        public readonly ?string $borrowed_equipment_id,
        public readonly ?string $equipment_type_id,
        public readonly ?string $serial_number = null,
        public readonly ?array $equipment_type = null,
        public readonly ?string $note = null,
    ) {
        if ($this->move === EquipmentMove::Return && $this->borrowed_equipment_id === null) {
            throw new InvalidArgumentException('A return has to say what is taken back.');
        }

        if ($this->move === EquipmentMove::Lend && $this->equipment_type_id === null) {
            throw new InvalidArgumentException('A loan has to say what kind of equipment is lent.');
        }
    }

    /**
     * Whether the line lends something to the customer.
     *
     * @return bool
     */
    public function lends(): bool
    {
        return $this->move === EquipmentMove::Lend;
    }

    /**
     * Whether the line takes something back.
     *
     * @return bool
     */
    public function takesBack(): bool
    {
        return $this->move === EquipmentMove::Return;
    }

    /**
     * Reads one line back from the stored shape.
     *
     * @param array<string, mixed> $line The stored line.
     * @return self
     * @throws \InvalidArgumentException When the line is not one a proposal can ask for.
     */
    public static function fromArray(array $line): self
    {
        $move = EquipmentMove::tryFrom((string)($line['move'] ?? ''));

        if ($move === null) {
            throw new InvalidArgumentException('An equipment line has to say whether it lends or returns.');
        }

        return new self(
            id: self::text($line, 'id') ?? Text::uuid(),
            move: $move,
            borrowed_equipment_id: self::text($line, 'borrowed_equipment_id'),
            equipment_type_id: self::text($line, 'equipment_type_id'),
            serial_number: self::text($line, 'serial_number'),
            equipment_type: isset($line['equipment_type']) && is_array($line['equipment_type'])
                ? $line['equipment_type']
                : null,
            note: self::text($line, 'note'),
        );
    }

    /**
     * Writes the line out in the shape it is stored in.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'move' => $this->move->value,
            'borrowed_equipment_id' => $this->borrowed_equipment_id,
            'equipment_type_id' => $this->equipment_type_id,
            'serial_number' => $this->serial_number,
            'equipment_type' => $this->equipment_type,
            'note' => $this->note,
        ];
    }

    /**
     * A stored value read as text, with an empty one meaning nothing was said.
     *
     * @param array<string, mixed> $line The stored line.
     * @param string $key Which value.
     * @return string|null
     */
    private static function text(array $line, string $key): ?string
    {
        $value = $line[$key] ?? null;

        if (is_array($value)) {
            return null;
        }

        return $value === null || trim((string)$value) === '' ? null : trim((string)$value);
    }
}
