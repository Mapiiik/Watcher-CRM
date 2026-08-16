<?php
declare(strict_types=1);

namespace Dashboard\Card;

/**
 * A single card on the dashboard.
 *
 * A card owns a logical id (its registry key and the URL it is fetched under), the
 * roles it is meant for, and the data its template renders. Permissions in
 * `config/permissions.php` are granted per action rather than per card, so who may
 * see a card is decided here and enforced by {@see \Dashboard\Card\CardRegistryInterface},
 * not by the rule set.
 *
 * A card that cannot answer cheaply says so through {@see self::deferred()} and is then
 * fetched on its own request once it scrolls into view, so one slow card does not hold
 * up the page.
 */
interface DashboardCardInterface
{
    /**
     * Logical card id — the registry key and the last segment of its URL.
     *
     * @return string
     */
    public function id(): string;

    /**
     * The heading the card is drawn under.
     *
     * @return string
     */
    public function title(): string;

    /**
     * Roles this card is offered to. An empty list offers it to every role.
     *
     * @return list<string>
     */
    public function roles(): array;

    /**
     * Whether the card is fetched on its own request rather than rendered with the page.
     *
     * @return bool
     */
    public function deferred(): bool;

    /**
     * The template under `templates/element/Dashboard/` that draws this card.
     *
     * @return string
     */
    public function template(): string;

    /**
     * The variables the template is given.
     *
     * @return array<string, mixed>
     */
    public function data(): array;
}
