<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use InvalidArgumentException;

/**
 * CustomerMessage Entity
 *
 * @property string $id
 * @property string $customer_id
 * @property \App\Model\Enum\CustomerMessageType $type
 * @property \App\Model\Enum\CustomerMessageDirection $direction
 * @property array $recipients
 * @property string $subject
 * @property string $body
 * @property \App\Model\Enum\CustomerMessageBodyFormat $body_format
 * @property \App\Model\Enum\CustomerMessageDeliveryStatus $delivery_status
 * @property \Cake\I18n\DateTime|null $processed
 * @property string|null $identifier
 * @property \Cake\I18n\DateTime $created
 * @property string $created_by
 * @property \Cake\I18n\DateTime $modified
 * @property string $modified_by
 * @property ?array $attachments
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\AppUser $creator
 * @property \App\Model\Entity\AppUser $modifier
 */
class CustomerMessage extends Entity
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
        'customer_id' => true,
        'type' => true,
        'direction' => true,
        'recipients' => true,
        'subject' => true,
        'body' => true,
        'body_format' => true,
        'delivery_status' => true,
        'processed' => true,
        'identifier' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'attachments' => true,
        'customer' => true,
        'creator' => true,
        'modifier' => true,
    ];

    /**
     * Convert recipients to array if string
     *
     * Allowed separators: " ,;" (space, comma, semicolon)
     *
     * @param mixed $recipientsInput Recipients input
     * @return array<string>
     */
    protected function _setRecipients(mixed $recipientsInput): array
    {
        $inputCopy = $recipientsInput;

        if (is_string($inputCopy)) {
            $inputCopy = explode(' ', str_replace([',', ';'], ' ', $inputCopy));
        }

        if (is_array($inputCopy)) {
            $recipientsOutput = [];
            foreach ($inputCopy as $item) {
                if ($item instanceof Email) {
                    // emails
                    $recipientsOutput[] = str_replace(' ', '', $item->email);
                } elseif ($item instanceof Phone) {
                    // phones
                    $recipientsOutput[] = str_replace(' ', '', $item->phone);
                } elseif (is_string($item)) {
                    // strings
                    $item = trim($item);
                    if (!empty($item)) {
                        $recipientsOutput[] = str_replace(' ', '', $item);
                    }
                } else {
                    throw new InvalidArgumentException("Invalid recipients data: $recipientsInput");
                }
            }

            return $recipientsOutput;
        }

        throw new InvalidArgumentException("Invalid recipients data: $recipientsInput");
    }
}
