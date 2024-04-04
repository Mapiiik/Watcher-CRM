<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Model\Entity;

use Cake\I18n\Date;
use Cake\ORM\Entity;

/**
 * Invoice Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property string|null $customer_id
 * @property int $number
 * @property int|null $variable_symbol
 * @property \Cake\I18n\Date|null $creation_date
 * @property \Cake\I18n\Date|null $due_date
 * @property string|null $text
 * @property \PhpCollective\DecimalObject\Decimal|null $total
 * @property \PhpCollective\DecimalObject\Decimal|null $debt
 * @property \Cake\I18n\Date|null $payment_date
 * @property bool $send_by_email
 * @property \Cake\I18n\DateTime|null $email_sent
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
    protected array $_accessible = [
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
        $now = Date::now();

        if ($this->debt->isPositive()) {
            $style = 'color: red;';
        }

        if ($this->debt->isPositive() && $this->due_date < $now) {
            $style = 'background-color: #ffc0c0; color: red;';
        }

        return $style;
    }
}
