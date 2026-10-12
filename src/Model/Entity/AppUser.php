<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Access\Model\Entity\User;
use Override;

/**
 * Application specific User Entity with non plugin conform field(s)
 *
 * @property bool $holds_tasks
 * @property string $customer_id
 * @property array|null $user_settings
 * @property string $name
 * @property string $name_for_lists
 */
class AppUser extends User
{
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
    #[Override]
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
            'customer-portal' => __('Customer Portal'),
            'customer-identification' => __('Customer Identification'),
        ];
    }
}
