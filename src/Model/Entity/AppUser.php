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
 * @property \Cake\I18n\Date|null $activation_date
 * @property \Cake\I18n\Date|null $tos_date
 * @property string|null $secret
 * @property bool $secret_verified
 * @property \Cake\I18n\Date|null $last_login
 * @property \Cake\I18n\Date $created
 * @property \Cake\I18n\Date $modified
 * @property string $customer_id
 * @property array|null $user_settings
 */
class AppUser extends User
{
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
