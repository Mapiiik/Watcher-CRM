<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Contracts\Check\ContractCheckRegistry;
use App\Contracts\Unsigned\UnsignedPaperwork;
use Cake\I18n\Date;
use Dashboard\Card\AbstractDashboardCard;
use Override;
use Settings\Utility\Settings;

/**
 * How many running services are being carried on paperwork nobody has signed.
 *
 * The findings card already gives this a line among the other contract checks. It gets a
 * card of its own as well because the line answers "is there anything wrong" and the card
 * answers "how many people are we about to cut off", which is the question somebody has to
 * act on before the nightly run does it for them.
 *
 * The count is broken into the three things that can be done about a version rather than
 * left as one number, because one number cannot be acted on: what is still inside every
 * deadline wants a phone call, what is being written to wants somebody to check the address
 * the letters are going to, and what is past the blocking deadline is about to cost the
 * customer their service. The three are cut so that they add up to the whole.
 *
 * Versions rather than customers. One customer can be carrying several unsigned versions and
 * each is its own piece of paper to chase, so this is a count of work rather than of people -
 * which is also what the overview behind the link holds, so the two agree.
 */
class UnsignedContractsCard extends AbstractDashboardCard
{
    /**
     * Where the settings say how long a running service may go unsigned.
     *
     * Named apart from the inherited SETTINGS_PATH rather than overriding it: the waits are
     * the contracts' business and belong beside the automation that acts on them, while what
     * the card inherits reads dashboard settings. One constant for both would have the
     * inherited helpers looking in the wrong place.
     */
    private const UNSIGNED_PATH = 'core.contracts.unsigned';

    /**
     * The waits, where the settings name none.
     */
    private const BLOCK_AFTER_INSTALLATION_DAYS = 10;

    private const BLOCK_AFTER_VALID_FROM_DAYS = 20;

    private const NOTIFY_AFTER_INSTALLATION_DAYS = 5;

    private const NOTIFY_AFTER_VALID_FROM_DAYS = 10;

    /**
     * @param \App\Contracts\Unsigned\UnsignedPaperwork $paperwork What counts as unsigned.
     */
    public function __construct(private UnsignedPaperwork $paperwork)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unsigned_contracts';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Running Services Without Papers');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['sales-manager', 'sales-representative', 'bookkeeper', 'network-manager'];
    }

    /**
     * Two counts over every version on file is not something to make the dashboard wait for.
     *
     * @return bool
     */
    #[Override]
    public function deferred(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        $today = Date::today();

        // Three nested sets, widest first. No wait at all is every version that has taken
        // effect and come back unsigned - a day past its start is already one of these.
        $total = $this->paperwork->findDue(0, 0, $today)->count();

        $notified = $this->paperwork->findDue(
            $this->wait('notifications.after_installation_days', self::NOTIFY_AFTER_INSTALLATION_DAYS),
            $this->wait('notifications.after_valid_from_days', self::NOTIFY_AFTER_VALID_FROM_DAYS),
            $today,
        )->count();

        $blocking = $this->paperwork->findDue(
            $this->wait('blocking.after_installation_days', self::BLOCK_AFTER_INSTALLATION_DAYS),
            $this->wait('blocking.after_valid_from_days', self::BLOCK_AFTER_VALID_FROM_DAYS),
            $today,
        )->count();

        // Cut into slices that do not overlap. The clamps are for the settings being set the
        // wrong way round - reminders due later than disconnection - which nests the sets the
        // other way and would otherwise show a negative count rather than a wrong one.
        return [
            'waiting' => max(0, $total - $notified),
            'notifying' => max(0, $notified - $blocking),
            'blocking' => $blocking,
            'url' => $this->overviewUrl(),
        ];
    }

    /**
     * The contract problems overview, showing this finding and nothing else.
     *
     * Every other check has to be named as off rather than left out: the overview reads a
     * check that is not in the query string as being at its default, so a link that only
     * switched one on would arrive with the rest switched on beside it and hold a different
     * number than the card that led there.
     *
     * @return array<string, mixed>
     */
    private function overviewUrl(): array
    {
        $checks = [];
        foreach ((new ContractCheckRegistry())->all() as $check) {
            $checks[$check->id()] = (int)($check->id() === 'unsigned_contract');
        }

        return [
            'controller' => 'Overviews',
            'action' => 'overviewOfContractProblems',
            'customer_id' => false,
            // The card counts the day's work, so the link has to ask for the day's work.
            '?' => ['checks' => $checks, 'ignore_inactive' => 1],
        ];
    }

    /**
     * A wait, in days, as the settings have it.
     *
     * @param string $key The key under this card's own settings path.
     * @param int $default What to use where the settings say nothing.
     * @return int
     */
    private function wait(string $key, int $default): int
    {
        $value = Settings::get(self::UNSIGNED_PATH . '.' . $key, $default);

        return is_numeric($value) ? (int)$value : $default;
    }
}
