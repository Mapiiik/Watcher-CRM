<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Model\Enum\ContractPrintType;
use App\Model\Entity\Billing;
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
        if (!in_array(
            $data->type,
            [
                ContractPrintType::HandoverInstallation,
                ContractPrintType::HandoverUninstallation,
            ],
            true,
        )) {
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
        } elseif ($data->contract->__isset('access_point')) {
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
     *  - active billings applicable at the contract version start
     *  - future billings starting after the contract version start
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
        if ($data->contractVersion === null) {
            return;
        }

        $billings = new Collection($data->contract->billings);

        $data->activeBillings = $billings->reject(
            function (Billing $billing) use ($data) {
                return (
                        $billing->__isset('billing_from')
                        && $billing->billing_from > $data->contractVersion->valid_from
                    ) || (
                        $billing->__isset('billing_until')
                        && $billing->billing_until < $data->contractVersion->valid_from
                    );
            },
        );

        $data->futureBillings = $billings->reject(
            function (Billing $billing) use ($data) {
                return (
                        $billing->__isset('billing_from')
                        && $billing->billing_from <= $data->contractVersion->valid_from
                    ) || (
                        $billing->__isset('billing_until')
                        && $billing->billing_until < $data->contractVersion->valid_from
                    );
            },
        );
    }
}
