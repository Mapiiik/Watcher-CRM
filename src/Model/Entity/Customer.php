<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Customer Entity
 *
 * @property int $id
 * @property int $dealer
 * @property string|null $title
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $company
 * @property int $taxe_id
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $bank_code
 * @property int $modified_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int $created_by
 * @property \Cake\I18n\FrozenTime|null $created
 * @property string|null $ic
 * @property string|null $dic
 * @property string|null $www
 * @property string|null $internal_note
 * @property int $invoice_delivery
 * @property string|null $note
 * @property string|null $identity_card_number
 * @property \Cake\I18n\FrozenDate|null $date_of_birth
 * @property \Cake\I18n\FrozenDate|null $termination_date
 * @property bool|null $agree_gdpr
 * @property bool|null $agree_mailing_outages
 * @property bool|null $agree_mailing_commercial
 * @property bool|null $agree_mailing_billing
 *
 * @property \App\Model\Entity\Tax $tax
 * @property \App\Model\Entity\Address[] $addresses
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\Contract[] $contracts
 * @property \App\Model\Entity\Email[] $emails
 * @property \App\Model\Entity\Ip[] $ips
 * @property \App\Model\Entity\LabelCustomer[] $label_customers
 * @property \App\Model\Entity\Login[] $logins
 * @property \App\Model\Entity\Phone[] $phones
 * @property \App\Model\Entity\RemovedIp[] $removed_ips
 * @property \App\Model\Entity\SoldEquipment[] $sold_equipments
 * @property \App\Model\Entity\Task[] $tasks
 */
class Customer extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'dealer' => true,
        'title' => true,
        'first_name' => true,
        'last_name' => true,
        'suffix' => true,
        'company' => true,
        'taxe_id' => true,
        'bank_name' => true,
        'bank_account' => true,
        'bank_code' => true,
        'modified_by' => true,
        'modified' => true,
        'created_by' => true,
        'created' => true,
        'ic' => true,
        'dic' => true,
        'www' => true,
        'internal_note' => true,
        'invoice_delivery' => true,
        'note' => true,
        'identity_card_number' => true,
        'date_of_birth' => true,
        'termination_date' => true,
        'agree_gdpr' => true,
        'agree_mailing_outages' => true,
        'agree_mailing_commercial' => true,
        'agree_mailing_billing' => true,
        'tax' => true,
        'addresses' => true,
        'billings' => true,
        'borrowed_equipments' => true,
        'contracts' => true,
        'emails' => true,
        'ips' => true,
        'label_customers' => true,
        'logins' => true,
        'phones' => true,
        'removed_ips' => true,
        'sold_equipments' => true,
        'tasks' => true,
    ];
}
