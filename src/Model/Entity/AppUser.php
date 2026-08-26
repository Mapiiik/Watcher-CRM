<?php
declare(strict_types=1);

namespace App\Model\Entity;

use CakeDC\Users\Model\Entity\User;

/**
 * Application specific User Entity with non plugin conform field(s)
 *
 * @property string $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property bool $active
 * @property bool $holds_tasks
 * @property \Cake\I18n\Date|null $activation_date
 * @property \Cake\I18n\Date|null $tos_date
 * @property string|null $secret
 * @property bool $secret_verified
 * @property \Cake\I18n\Date|null $last_login
 * @property \Cake\I18n\Date $created
 * @property \Cake\I18n\Date $modified
 * @property string $customer_id
 * @property array|null $user_settings
 * @property string $name
 * @property string $name_for_lists
 */
class AppUser extends User
{
    /**
     * Fields left out wherever the account is written out whole.
     *
     * A person is handed out beside the records they are named on - a task they are working on,
     * say - and the row that attached them there travels with them unasked. What that row says
     * is a fact about the record rather than about the person, and out in an answer it is noise.
     *
     * The six above it are the plugin's own list, repeated because a property replaces the one
     * it inherits rather than adding to it. Anything the plugin adds to its list has to be
     * copied here as well.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'additional_data',
        'api_token',
        'password',
        'secret',
        'token',
        'token_expires',
        '_joinData',
    ];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
        'is_superuser' => false,
        'role' => true,
    ];

    /**
     * What the person is called.
     *
     * The account name belongs to {@see _getNameForLists()} rather than here. A list has to tell
     * two people of the same name apart, and says which account each one is; a cell standing in a
     * row that is already about one task has nothing to tell apart, and the brackets only get in
     * the way of reading it.
     *
     * @return string
     */
    protected function _getName(): string
    {
        $name = implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ]));

        // An account with no name filled in still has to read as somebody rather than as an empty
        // cell, and the only thing left to call it by is the account itself.
        return $name !== '' ? $name : (string)$this->username;
    }

    /**
     * What the person is called, surname first and with the account named, for picking one out
     * of a list.
     *
     * @return string
     */
    protected function _getNameForLists(): string
    {
        $name = implode(' ', array_filter([
            $this->last_name,
            $this->first_name,
        ]));

        return $name . ' (' . $this->username . ')';
    }

    /**
     * Get role options method
     *
     * @return array<string, string>
     */
    public function getRoleOptions(): array
    {
        return [
            'user' => __('User'),
            'customer-service-technician' => __('Customer Service Technician'),
            'network-technician' => __('Network Technician'),
            'network-manager' => __('Network Manager'),
            'sales-representative' => __('Sales Representative'),
            'sales-manager' => __('Sales Manager'),
            'bookkeeper' => __('Bookkeeper'),
            'admin' => __('Admin'),
            'api' => __('API'),
        ];
    }

    /**
     * Get role name method
     *
     * @return string
     */
    public function getRoleName(): string
    {
        return $this->getRoleOptions()[$this->role] ?? $this->role;
    }
}
