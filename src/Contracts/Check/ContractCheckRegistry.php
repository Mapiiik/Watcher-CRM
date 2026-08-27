<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\AbstractCheckRegistry;
use App\Model\Table\BillingsTable;

/**
 * Registry of the checks that can be run against the contracts on file.
 *
 * This is the single extension point: register a check here, give it a template beside the
 * others, and the dashboard card, the overview and the contract itself pick it up.
 *
 * @extends \App\Check\AbstractCheckRegistry<\App\Contracts\Check\ContractCheckInterface>
 */
final class ContractCheckRegistry extends AbstractCheckRegistry
{
    /**
     * Registered in the order they are listed.
     *
     * @param bool $ignore_inactive Whether the checks keep to what is running. Each applies
     *   it to its own subject - the contract for most of them, the finding itself where the
     *   contract is beside the point - so that the answer is about the record being reported.
     *   Off, the checks report the history as well, which is what putting the history
     *   straight needs and what daily work does not.
     * @param string|null $contract_id One contract to ask about, rather than the whole file.
     *   This is what lets a contract show its own findings.
     */
    public function __construct(private bool $ignore_inactive = true, private ?string $contract_id = null)
    {
        /** @var \App\Model\Table\BillingsTable $billings */
        $billings = $this->fetchTable(BillingsTable::class);

        $this->factories = [
            'billing_gap' =>
                fn(): ContractCheckInterface => new BillingGapCheck(
                    $billings,
                    $this->ignore_inactive,
                    $this->contract_id,
                ),
        ];
    }
}
