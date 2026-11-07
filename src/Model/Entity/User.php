<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Access\Model\Entity\User as AccessUser;
use Override;

/**
 * An account, with the roles this application knows and whether tasks can be given to it.
 *
 * @property bool $holds_tasks
 */
class User extends AccessUser
{
    /**
     * What the forms of an account set, which here also say whether tasks can be given to it.
     *
     * @return array<string, bool>
     */
    #[Override]
    public function formFields(): array
    {
        return parent::formFields() + ['holds_tasks' => true];
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
            'manager' => __('Manager'),
            'admin' => __('Admin'),
            'api' => __('API'),
            'watcher-nms' => __('Watcher NMS'),
            'customer-portal' => __('Customer Portal'),
            'customer-identification' => __('Customer Identification'),
        ];
    }
}
