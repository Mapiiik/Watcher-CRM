<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Access\Model\Entity\User;
use Override;

/**
 * An account, with the roles this application knows.
 *
 * @property string|null $customer_id
 */
class AppUser extends User
{
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
