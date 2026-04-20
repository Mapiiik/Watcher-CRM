<?php
declare(strict_types=1);

namespace Bookkeeping\Model\Entity;

use App\Model\Entity\AppEntity;
use Cake\I18n\Date;

/**
 * Invoice Entity
 *
 * @property string $id
 * @property string|null $customer_id
 * @property string $number
 * @property string|null $variable_symbol
 * @property \Cake\I18n\Date $creation_date
 * @property \Cake\I18n\Date $due_date
 * @property string|null $text
 * @property \PhpCollective\DecimalObject\Decimal $total
 * @property \PhpCollective\DecimalObject\Decimal $debt
 * @property \Cake\I18n\Date|null $payment_date
 * @property bool $send_by_email
 * @property \Cake\I18n\DateTime|null $email_sent
 * @property string|null $accounting_identifier
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 */
class Invoice extends AppEntity
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
        'accounting_identifier' => true,
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

        if (isset($this->debt) && $this->debt->isPositive()) {
            $style = 'color: red;';
        }

        if (isset($this->debt) && $this->debt->isPositive() && $this->due_date < $now) {
            $style = 'background-color: #ffc0c0; color: red;';
        }

        return $style;
    }
}
