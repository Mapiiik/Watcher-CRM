<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Model\Entity\Billing;
use App\Model\Enum\ContractPrintType;
use Cake\Collection\Collection;
use Cake\Database\Exception\MissingConnectionException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Radius\Model\Table\AccountsTable;

/**
 * Enriches validated ContractPrintData with derived and auxiliary information
 * required for contract-related print outputs.
 *
 * This class:
 *  - does NOT perform validation (handled by ContractPrintValidator)
 *  - does NOT render PDFs or views
 *  - does NOT perform redirects or Flash messaging
 *
 * It operates purely on already prepared ContractPrintData and
 * mutates it by adding context-dependent data needed for printing,
 * such as technical connection details and time-relevant billings.
 */
final class ContractPrintDataEnricher
{
    use LocatorAwareTrait;

    /**
     * Enriches the given ContractPrintData instance with all
     * additional information required for printing.
     *
     * This method is intended to be called only after successful validation.
     * It mutates the provided DTO by filling optional properties
     * based on document type and contract context.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data
     * @param array $query Original query parameters from the request
     * @return void
     */
    public function enrich(
        ContractPrintData $data,
        array $query,
    ): void {
        $this->enrichTechnicalDetails($data, $query);
        $this->enrichBillings($data);
    }

    /**
     * Enriches technical connection details for handover protocol documents.
     *
     * This includes:
     *  - access point name
     *  - RADIUS username
     *  - RADIUS password
     *
     * Values are resolved in the following order:
     *  1. Explicit values provided in the query string
     *  2. Data derived from the contract entity
     *  3. Data fetched from the RADIUS system (if available)
     *
     * For non-handover document types, this method does nothing.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data
     * @param array $query
     * @return void
     */
    private function enrichTechnicalDetails(
        ContractPrintData $data,
        array $query,
    ): void {
        // Technical details are only relevant for handover protocol documents
        if (
            !in_array(
                $data->type,
                [
                    ContractPrintType::HandoverInstallation,
                    ContractPrintType::HandoverUninstallation,
                ],
                true,
            )
        ) {
            return;
        }

        $technicalData = new ContractPrintTechnicalData();

        try {
            /** @var \Radius\Model\Entity\Account|null $radiusAccount */
            $radiusAccount = $this->fetchTable(AccountsTable::class)
                ->find()
                ->where([
                    'contract_id' => $data->contract->id,
                    'active' => true,
                ])
                ->orderBy(['id' => 'DESC'])
                ->limit(1)
                ->first();

            $radiusConnected = true;
        } catch (MissingConnectionException) {
            $radiusAccount = null;
            $radiusConnected = false;
        }

        if (!empty($query['access_point'])) {
            $technicalData->accessPoint = (string)$query['access_point'];
        } elseif ($data->contract->access_point !== null && $data->contract->access_point['name'] !== null) {
            $technicalData->accessPoint = (string)$data->contract->access_point['name'];
        }

        if (!empty($query['radius_username'])) {
            $technicalData->radiusUsername = (string)$query['radius_username'];
        } elseif ($radiusConnected && isset($radiusAccount->username)) {
            $technicalData->radiusUsername = (string)$radiusAccount->username;
        }

        if (!empty($query['radius_password'])) {
            $technicalData->radiusPassword = (string)$query['radius_password'];
        } elseif ($radiusConnected && isset($radiusAccount->password)) {
            $technicalData->radiusPassword = (string)$radiusAccount->password;
        }

        $data->technicalDetails = $technicalData;
    }

    /**
     * Enriches billing collections relevant for the printed document.
     *
     * Billings are split only by time relevance:
     *  - active billings applicable at the document reference date
     *  - future billings starting after the document reference date
     *
     * The reference date is determined as follows:
     *  - contract version start date for standard contract documents
     *  - amendment effective date for contract amendment documents
     *
     * Further semantic splitting (e.g. individual vs standard billings)
     * is intentionally deferred to DTO getters.
     *
     * If no contract version is selected, no billing data is enriched.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data
     * @return void
     */
    private function enrichBillings(
        ContractPrintData $data,
    ): void {
        // Billing enrichment only makes sense if we have a specific contract version context
        if ($data->contractVersionToBeExecuted === null) {
            return;
        }

        // Determine the reference date for billing relevance based on document type
        $referenceDate = $data->contractVersionToBeExecuted->valid_from;
        if (
            $data->type === ContractPrintType::ContractAmendment
            && $data->effectiveDateOfAmendment !== null
        ) {
            $referenceDate = $data->effectiveDateOfAmendment;
        }

        // Use CakePHP's Collection to filter billings based on their relevance to the reference date
        $billings = new Collection($data->contract->billings);

        // Active billings are those that are applicable at the reference date
        $data->activeBillings = $billings->reject(
            function (Billing $billing) use ($referenceDate) {
                return (
                        $billing->billing_from !== null
                        && $billing->billing_from > $referenceDate
                    ) || (
                        $billing->billing_until !== null
                        && $billing->billing_until < $referenceDate
                    );
            },
        );

        // Future billings are those that start after the reference date
        $data->futureBillings = $billings->reject(
            function (Billing $billing) use ($referenceDate) {
                return (
                        $billing->billing_from !== null
                        && $billing->billing_from <= $referenceDate
                    ) || (
                        $billing->billing_until !== null
                        && $billing->billing_until < $referenceDate
                    );
            },
        );
    }
}
