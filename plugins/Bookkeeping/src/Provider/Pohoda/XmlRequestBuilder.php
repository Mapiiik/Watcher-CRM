<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use Cake\I18n\DateTime;
use Riesenia\Pohoda;
use RuntimeException;
use Settings\Utility\Settings;

/**
 * Class XmlRequestBuilder
 *
 * Responsible for generating XML requests for Pohoda mServer.
 * Uses the Riesenia\Pohoda library to construct valid XML structures.
 */
class XmlRequestBuilder
{
    /**
     * Build XML request for invoice synchronization.
     *
     * @param \Cake\I18n\DateTime $lastChanges Timestamp of last successful sync.
     * @return string XML request body.
     */
    public function buildSyncRequest(DateTime $lastChanges): string
    {
        Pohoda::$encoding = 'UTF-8'; // Set encoding for Pohoda library

        $pohoda = new Pohoda(
            Settings::getString(
                PohodaProvider::SETTINGS_ROOT . '.issuer.identity_number',
                '00000000',
            ),
        );
        $pohoda->setApplicationName('Watcher CRM');

        // Open Pohoda request (null for in memory, '001' for ID, description)
        $pohoda->open(null, '001', 'Request to export invoice selection');

        // Create list request for invoices
        $request = $pohoda->createListRequest([
            'type' => 'Invoice',
            'invoiceType' => 'issuedInvoice',
        ]);

        // Add filter for last changes
        /*
        $request->addFilter([
            'lastChanges' => $lastChanges, # all records that have a "saved" date later than this date
        ]);
        */
        $request->addQueryFilter([
            'textName' => "(Uloženo >= {$lastChanges->toDateTimeString()}; Likv. >= {$lastChanges->toDateString()})",
            'filter' =>
                '('
                . "FA.DatSave>=CONVERT(DATETIME, '{$lastChanges->format('m/d/Y H:i:s')}', 101)"
                . ' OR '
                . "FA.DatLikv>=CONVERT(DATETIME, '{$lastChanges->format('m/d/Y')}', 101)"
                . ')',
        ]);

        $pohoda->addItem('list_001', $request);

        // Close and return the generated XML
        $result = $pohoda->close();
        if (is_int($result)) {
            throw new RuntimeException('Unexpected integer return from Pohoda::close() in memory mode.');
        }

        return $result;
    }
}
