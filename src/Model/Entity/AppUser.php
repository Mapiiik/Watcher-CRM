<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Access\Model\Entity\User;
use Override;

/**
 * An account, with the roles this application knows and whether tasks can be given to it.
 *
 * @property bool $holds_tasks
 */
class AppUser extends User
{
    /**
     * An account, which a form may also say holds tasks.
     *
     * @param array<string, mixed> $properties The fields of the account.
     * @param array<string, mixed> $options How to set them.
     */
    public function __construct(array $properties = [], array $options = [])
    {
        parent::__construct($properties, $options);

        // a form of the account says whether tasks can be given to it
        $this->setAccess('holds_tasks', true);
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
