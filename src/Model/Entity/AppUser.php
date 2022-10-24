<?php
declare(strict_types=1);

namespace App\Model\Entity;

use CakeDC\Users\Model\Entity\User;

/**
 * Application specific User Entity with non plugin conform field(s)
 */
class AppUser extends User
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'is_superuser' => false,
        'role' => true,
    ];

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
            'sales-representative' => __('Sales Representative'),
            'sales-manager' => __('Sales Manager'),
            'bookkeeper' => __('Bookkeeper'),
            'admin' => __('Admin'),
            'api' => __('API'),
        ];
    }

    /**
     * Get dealer state method
     *
     * @return string
     */
    public function getRoleName(): string
    {
        return $this->getRoleOptions()[$this->role] ?? $this->role;
    }
}
