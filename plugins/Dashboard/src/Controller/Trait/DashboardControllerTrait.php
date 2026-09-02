<?php
declare(strict_types=1);

namespace Dashboard\Controller\Trait;

use Cake\Http\Exception\NotFoundException;
use Dashboard\Card\CardRegistryInterface;

/**
 * Dashboard Controller Trait
 *
 * The two actions the dashboard is: the page, and one card fetched on its own. Which cards
 * there are is left to the application, which answers {@see self::registry()} with its own.
 *
 * The views come from this plugin, so the builder is pointed at it. Templates fall through
 * to the application afterwards, which is what lets a card's own template live there - and
 * what lets an application replace the frame around them without touching this.
 *
 * @psalm-require-extends \Cake\Controller\Controller
 */
trait DashboardControllerTrait
{
    /**
     * The page: every card the signed-in role is offered.
     *
     * Named after what it draws rather than `index`, because the bare `/dashboard` is not
     * this page to have. A plugin that ships a webroot has it linked into the application's
     * own under the plugin's name, and this plugin is named after the page it draws - so a
     * web server that answers directories itself takes that path before the router is ever
     * asked. On nginx that is a 403, on Caddy it is not, which is what makes it a fault that
     * only shows in production.
     *
     * @return void Renders view
     */
    public function cards(): void
    {
        $cards = $this->registry()->forRole();

        $this->viewBuilder()->setPlugin('Dashboard');
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
            throw new NotFoundException(__d('dashboard', 'There is no such card.'));
        }

        $this->viewBuilder()->setPlugin('Dashboard')->setLayout('ajax');
        $this->set(compact('card'));
    }

    /**
     * The cards this application offers, built for whoever is signed in.
     *
     * @return \Dashboard\Card\CardRegistryInterface
     */
    abstract protected function registry(): CardRegistryInterface;
}
