<?php
declare(strict_types=1);

namespace App\Service\ConnectionHistory;

/**
 * What one run of the connection history update did.
 */
class UpdateSummary
{
    /**
     * Accounts whose intervals were walked.
     */
    public int $accounts = 0;

    /**
     * Intervals opened.
     */
    public int $opened = 0;

    /**
     * Intervals opened because the account was moved to another customer or
     * contract rather than because anything changed on the network.
     */
    public int $openedByAccountChange = 0;

    /**
     * Running intervals whose end was pushed forward.
     */
    public int $extended = 0;

    /**
     * Intervals already covered by what was recorded before.
     */
    public int $skipped = 0;

    /**
     * Intervals filled in from the NMS.
     */
    public int $enriched = 0;

    /**
     * Sources that could not be reached and were therefore left alone.
     *
     * @var list<string>
     */
    public array $unavailableSources = [];

    /**
     * Whether anything at all was written.
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        return $this->opened > 0 || $this->extended > 0 || $this->enriched > 0;
    }
}
