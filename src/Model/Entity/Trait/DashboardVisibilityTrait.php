<?php
declare(strict_types=1);

namespace App\Model\Entity\Trait;

use App\Model\Entity\AppUser;

/**
 * Shared by the records an operator can pick out for the dashboard.
 *
 * Two columns carry it: `show_on_dashboard` says whether it is drawn at all, and
 * `dashboard_roles` says to whom. Naming no role is how "to everybody" is said, so a
 * record does not silently stop being drawn when a role is added.
 */
trait DashboardVisibilityTrait
{
    /**
     * The roles this record is drawn for, named as the form offers them rather than as
     * they are stored.
     *
     * @return list<string>
     */
    protected function _getDashboardRoleNames(): array
    {
        $options = (new AppUser())->getRoleOptions();

        return array_values(array_map(
            fn(string $role): string => $options[$role] ?? $role,
            $this->get('dashboard_roles') ?? [],
        ));
    }

    /**
     * Whether an operator in the given role is shown this record on the dashboard.
     *
     * @param string|null $role The role to ask about.
     * @return bool
     */
    public function isOnDashboardFor(?string $role): bool
    {
        if (!$this->get('show_on_dashboard')) {
            return false;
        }

        $roles = $this->get('dashboard_roles');
        if (empty($roles)) {
            return true;
        }

        return $role !== null && in_array($role, $roles, true);
    }
}
