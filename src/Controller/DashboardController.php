<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dashboard\DashboardCardRegistry;
use Dashboard\Card\CardRegistryInterface;
use Dashboard\Controller\Trait\DashboardControllerTrait;
use Override;

/**
 * Dashboard Controller
 *
 * The landing page: what wants attention today. The two actions come from the plugin; what
 * this says is which cards this application has and who is asking for them.
 */
class DashboardController extends AppController
{
    use DashboardControllerTrait;

    /**
     * The registry, built for whoever is signed in.
     *
     * Tasks here are held by a user, so the identity itself is what a card asking "mine" is
     * given.
     *
     * @return \Dashboard\Card\CardRegistryInterface
     */
    #[Override]
    protected function registry(): CardRegistryInterface
    {
        $identity = $this->getRequest()->getAttribute('identity');

        $role = $identity['role'] ?? null;
        $user_id = $identity['id'] ?? null;

        return new DashboardCardRegistry(
            role: is_string($role) ? $role : null,
            user_id: is_string($user_id) ? $user_id : null,
        );
    }
}
