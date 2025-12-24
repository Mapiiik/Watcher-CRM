<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;
use RuntimeException;
use SimpleXMLElement;

/**
 * Class XmlParser
 *
 * Responsible for parsing XML responses returned by Pohoda mServer.
 * Extracts invoice data and converts it into InvoiceDraft objects.
 *
 * This class performs no validation or persistence logic.
 * It only reads, normalizes and maps XML data into a domain-neutral structure.
 */
class XmlParser
{
    /**
     * Parse invoices from XML file.
     *
     * @param string $filePath Path to uploaded DBF file.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function parseFile(string $filePath): array
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_file($filePath);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            throw new RuntimeException(__d(
                'bookkeeping',
                'Invalid XML file: {0}',
                [($errors[0]->message ?? __d('bookkeeping', 'Unknown XML error'))],
            ));
        }

        return $this->parseSimpleXML($xml);
    }

    /**
     * Parse invoices from XML response.
     *
     * @param \SimpleXMLElement $xml XML response from Pohoda.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function parseSimpleXML(SimpleXMLElement $xml): array
    {
        // Register namespaces
        $xml->registerXPathNamespace('rsp', 'http://www.stormware.cz/schema/version_2/response.xsd');
        $xml->registerXPathNamespace('lst', 'http://www.stormware.cz/schema/version_2/list.xsd');
        $xml->registerXPathNamespace('inv', 'http://www.stormware.cz/schema/version_2/invoice.xsd');
        $xml->registerXPathNamespace('typ', 'http://www.stormware.cz/schema/version_2/type.xsd');

        $invoices = $xml->xpath('//lst:invoice');
        $drafts = [];

        foreach ($invoices as $invoice) {
            $draft = new InvoiceDraft();

            // Invoice number
            $draft->number = $this->extract(
                $invoice,
                './inv:invoiceHeader/inv:number/typ:numberRequested',
            );

            // Variable symbol
            $value = $this->extract(
                $invoice,
                './inv:invoiceHeader/inv:symVar',
            );
            $draft->variableSymbol = $value !== null ? (int)$value : null;

            // Dates
            $draft->creationDate = $this->parseDate(
                $this->extract($invoice, './inv:invoiceHeader/inv:date'),
            );

            $draft->dueDate = $this->parseDate(
                $this->extract($invoice, './inv:invoiceHeader/inv:dateDue'),
            );

            $draft->paymentDate = $this->parseDate(
                $this->extract(
                    $invoice,
                    './inv:invoiceHeader/inv:liquidation/typ:date',
                ),
            );

            // Text
            $draft->text = $this->extract(
                $invoice,
                './inv:invoiceHeader/inv:text',
            );

            // Calculate total amount
            $priceNone = (float)$this->extract(
                $invoice,
                './inv:invoiceSummary/inv:homeCurrency/typ:priceNone',
                0,
            );
            $priceLowSum = (float)$this->extract(
                $invoice,
                './inv:invoiceSummary/inv:homeCurrency/typ:priceLowSum',
                0,
            );
            $priceHighSum = (float)$this->extract(
                $invoice,
                './inv:invoiceSummary/inv:homeCurrency/typ:priceHighSum',
                0,
            );

            $draft->total = Decimal::create(
                (string)($priceNone + $priceLowSum + $priceHighSum),
                2,
            );

            // Remaining debt
            $draft->debt = Decimal::create(
                (string)$this->extract(
                    $invoice,
                    './inv:invoiceHeader/inv:liquidation/typ:amountHome',
                    0,
                ),
                2,
            );

            // Optional metadata
            $draft->metadata['source'] = 'pohoda-xml';

            $drafts[] = $draft;
        }

        return $drafts;
    }

    /**
     * Parse date string into Cake Date object.
     *
     * @param string|null $value
     * @return \Cake\I18n\Date|null
     */
    private function parseDate(?string $value): ?Date
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new Date($value);
    }

    /**
     * Helper method to extract a single value via XPath.
     *
     * @param \SimpleXMLElement $xml
     * @param string $path
     * @param mixed $default
     * @return string|null
     */
    private function extract(SimpleXMLElement $xml, string $path, mixed $default = null): ?string
    {
        $nodes = $xml->xpath($path);

        if ($nodes && isset($nodes[0])) {
            return (string)$nodes[0];
        }

        return $default !== null ? (string)$default : null;
    }
}
