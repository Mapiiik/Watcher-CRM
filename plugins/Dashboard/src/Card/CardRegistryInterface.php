<?php
declare(strict_types=1);

namespace Dashboard\Card;

/**
 * What the dashboard asks for its cards.
 *
 * Which cards there are is the application's own business - one keeps customers and
 * invoices, another access points - so the registry is written there and only answers to
 * this much.
 *
 * Both answers are asked on behalf of somebody, because permissions are granted per action
 * and not per card: whoever a card is not meant for must not be handed it by asking for the
 * page, nor by asking for the card's own URL.
 */
interface CardRegistryInterface
{
    /**
     * The cards the signed-in role is offered, in the order they are to be drawn.
     *
     * @return list<\Dashboard\Card\DashboardCardInterface>
     */
    public function forRole(): array;

    /**
     * The card registered under the given id, or null where there is none or the signed-in
     * role is not offered it.
     *
     * @param string $id Registry key.
     * @return \Dashboard\Card\DashboardCardInterface|null
     */
    public function getAllowed(string $id): ?DashboardCardInterface;
}
