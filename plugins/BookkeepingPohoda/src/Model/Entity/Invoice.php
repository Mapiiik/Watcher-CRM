<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Model\Entity;

use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * Invoice Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property int|null $customer_id
 * @property int $number
 * @property int|null $variable_symbol
 * @property \Cake\I18n\FrozenDate|null $creation_date
 * @property \Cake\I18n\FrozenDate|null $due_date
 * @property string|null $text
 * @property float|null $total
 * @property float|null $debt
 * @property \Cake\I18n\FrozenDate|null $payment_date
 * @property bool $send_by_email
 * @property \Cake\I18n\FrozenTime|null $email_sent
 * @property \App\Model\Entity\Billing[] $items
 * @property string|null $note
 * @property string|null $internal_note
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 */
class Invoice extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer_id' => true,
        'number' => true,
        'variable_symbol' => true,
        'creation_date' => true,
        'due_date' => true,
        'text' => true,
        'total' => true,
        'debt' => true,
        'payment_date' => true,
        'send_by_email' => true,
        'email_sent' => true,
        'customer' => true,
    ];

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';
        $now = new FrozenDate();

        if ($this->debt > 0) {
            $style = 'color: red;';
        }

        if ($this->debt > 0 && $this->due_date < $now) {
            $style = 'background-color: #ffc0c0; color: red;';
        }

        return $style;
    }
}
