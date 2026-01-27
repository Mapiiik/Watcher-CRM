<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

/**
 * Provider responsible for supplying authentication credentials
 * for the Eurofaktura / E-racuni API.
 *
 * This class centralizes all logic related to selecting which
 * API identity (credentials) should be used for a given operation.
 *
 * Responsibilities:
 * - Provide default API credentials for general operations
 * - Provide dedicated credentials for invoice issuing (rate-limit sensitive)
 * - Act as a single source of truth for credential selection
 *
 * Design notes:
 * - Does NOT perform any HTTP or API communication
 * - Does NOT contain retry or rate-limit logic
 * - Returns immutable value objects (EurofakturaCredentials)
 *
 * This abstraction allows future extensions such as:
 * - credential rotation
 * - round-robin pools
 * - per-operation rate-limit strategies
 * - environment- or tenant-specific credentials
 */
final class EurofakturaCredentialsProvider
{
    /**
     * Return default API credentials.
     *
     * These credentials are intended for:
     * - synchronization operations
     * - partner imports
     * - PDF downloads
     * - non-critical or retry-safe API calls
     *
     * The default credentials typically represent the primary
     * Eurofaktura account configured for the application.
     *
     * @return \Bookkeeping\Provider\Eurofaktura\EurofakturaCredentials Default API credentials.
     */
    public function getDefault(): EurofakturaCredentials
    {
        return new EurofakturaCredentials(
            (string)env('EUROFAKTURA_USERNAME', ''),
            (string)env('EUROFAKTURA_SECRET_KEY', ''),
            (string)env('EUROFAKTURA_TOKEN', ''),
        );
    }

    /**
     * Return credentials dedicated for invoice issuing.
     *
     * These credentials are intended specifically for:
     * - SalesInvoiceCreate
     * - other write-heavy, rate-limit sensitive operations
     *
     * Using a separate API account for invoice issuing helps:
     * - reduce the risk of hitting global rate limits
     * - isolate critical write operations from background syncs
     * - improve overall system resilience
     *
     * If dedicated invoice credentials are not configured,
     * this method gracefully falls back to the default API credentials.
     *
     * @return \Bookkeeping\Provider\Eurofaktura\EurofakturaCredentials Credentials for invoice issuing.
     */
    public function getForInvoiceIssuing(): EurofakturaCredentials
    {
        return new EurofakturaCredentials(
            (string)env('EUROFAKTURA_INVOICES_USERNAME', env('EUROFAKTURA_USERNAME', '')),
            (string)env('EUROFAKTURA_INVOICES_SECRET_KEY', env('EUROFAKTURA_SECRET_KEY', '')),
            (string)env('EUROFAKTURA_INVOICES_TOKEN', env('EUROFAKTURA_TOKEN', '')),
        );
    }
}
