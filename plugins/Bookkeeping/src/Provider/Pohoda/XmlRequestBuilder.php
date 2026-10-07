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
                PohodaProvider::SETTINGS_ROOT . '.api.accounting_unit',
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
            'textName' => sprintf(
                '(Uloženo >= %s; Likv. >= %s)',
                $lastChanges->toDateTimeString(),
                $lastChanges->toDateString(),
            ),
            'filter' =>
                '('
                . sprintf("FA.DatSave>=CONVERT(DATETIME, '%s', 101)", $lastChanges->format('m/d/Y H:i:s'))
                . ' OR '
                . sprintf("FA.DatLikv>=CONVERT(DATETIME, '%s', 101)", $lastChanges->format('m/d/Y'))
                . ')',
        ]);

        $pohoda->addItem('list_001', $request);

        // Close and return the generated XML
        return $this->close($pohoda);
    }

    /**
     * Build XML request importing customers into the address book.
     *
     * Each customer is written as add-or-update by their external ID: the first run adds the
     * partner card, every later one finds it again and overwrites it. Each item is identified by
     * the customer number, so the response can be read back customer by customer.
     *
     * @param list<\App\Model\Entity\Customer> $customers Customers to import.
     * @return string XML request body.
     */
    public function buildPartnersRequest(array $customers): string
    {
        Pohoda::$encoding = 'UTF-8'; // Set encoding for Pohoda library

        $pohoda = new Pohoda(
            Settings::getString(
                PohodaProvider::SETTINGS_ROOT . '.api.accounting_unit',
                '00000000',
            ),
        );
        $pohoda->setApplicationName('Watcher CRM');

        $pohoda->open(null, 'adb', 'Import customers into the address book');

        foreach ($customers as $customer) {
            $extId = PohodaProvider::partnerExtId($customer);
            $address = $customer->billing_address;

            $identity = [
                'extId' => $extId,
            ];

            if ($address !== null) {
                $identity['address'] = [
                    'company' => $address->company ?? '',
                    'name' => $address->full_name,
                    'city' => $address->city ?? '',
                    'street' => $address->street_and_number,
                    'zip' => $address->zip ?? '',
                    'ico' => $customer->identity_number ?? '',
                    'dic' => $customer->vat_number ?? '',
                    'country' => $address->country->code ?? '',
                ];
            }

            $addressbook = $pohoda->createAddressbook([
                'identity' => $identity,
                // the external ID is not shown on the card, so the customer number is written
                // where somebody looking at it will see it
                'agreement' => $customer->number,
                'email' => $customer->billing_emails[0]->email ?? $customer->emails[0]->email ?? '',
                'phone' => $customer->billing_phones[0]->phone ?? $customer->phones[0]->phone ?? '',
            ]);
            $addressbook->addActionType('add/update', ['extId' => $extId]);

            $pohoda->addItem($customer->number, $addressbook);
        }

        return $this->close($pohoda);
    }

    /**
     * Close a request built in memory and hand back its XML.
     *
     * @param \Riesenia\Pohoda $pohoda Request opened in memory.
     * @return string XML request body.
     */
    private function close(Pohoda $pohoda): string
    {
        $result = $pohoda->close();
        if (is_int($result)) {
            throw new RuntimeException('Unexpected integer return from Pohoda::close() in memory mode.');
        }

        return $result;
    }
}
