<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dashboard\DashboardCardRegistry;
use Cake\Http\Exception\NotFoundException;

/**
 * Dashboard Controller
 *
 * The landing page: what wants attention today, drawn as cards chosen by the role of
 * whoever is signed in.
 */
class DashboardController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $cards = $this->registry()->forRole();

        $this->set(compact('cards'));
    }

    /**
     * Render a single card on its own, for the ones too slow to hold up the page.
     *
     * @param string|null $id Registry key of the card.
     * @return void Renders view
     * @throws \Cake\Http\Exception\NotFoundException When there is no such card, or the
     *   signed-in role is not offered it.
     */
    public function card(?string $id = null): void
    {
        $card = $id === null ? null : $this->registry()->getAllowed($id);
        if ($card === null) {
            throw new NotFoundException(__('There is no such card.'));
        }

        $this->viewBuilder()->setLayout('ajax');
        $this->set(compact('card'));
    }

    /**
     * The registry, built for whoever is signed in.
     *
     * @return \App\Dashboard\DashboardCardRegistry
     */
    private function registry(): DashboardCardRegistry
    {
        $identity = $this->getRequest()->getAttribute('identity');

        $role = $identity['role'] ?? null;
        $customer_id = $identity['customer_id'] ?? null;

        return new DashboardCardRegistry(
            role: is_string($role) ? $role : null,
            customer_id: is_string($customer_id) ? $customer_id : null,
        );
    }
}
