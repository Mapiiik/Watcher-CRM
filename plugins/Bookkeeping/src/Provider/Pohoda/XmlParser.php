<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use SimpleXMLElement;

/**
 * Class XmlParser
 *
 * Responsible for parsing XML responses returned by Pohoda mServer.
 * Extracts invoice data and converts it into structured arrays.
 */
class XmlParser
{
    /**
     * Parse invoices from XML response.
     *
     * @param \SimpleXMLElement $xml XML response from Pohoda.
     * @return array Parsed invoice data.
     */
    public function parseInvoices(SimpleXMLElement $xml): array
    {
        // Register namespaces
        $xml->registerXPathNamespace('rsp', 'http://www.stormware.cz/schema/version_2/response.xsd');
        $xml->registerXPathNamespace('lst', 'http://www.stormware.cz/schema/version_2/list.xsd');
        $xml->registerXPathNamespace('inv', 'http://www.stormware.cz/schema/version_2/invoice.xsd');
        $xml->registerXPathNamespace('typ', 'http://www.stormware.cz/schema/version_2/type.xsd');

        $invoices = $xml->xpath('//lst:invoice');
        $invoicesData = [];

        foreach ($invoices as $invoice) {
            $invoiceInfo = [];

            $invoiceInfo['numberRequested'] =
                $this->extract($invoice, './inv:invoiceHeader/inv:number/typ:numberRequested');

            $invoiceInfo['symVar'] =
                $this->extract($invoice, './inv:invoiceHeader/inv:symVar');

            $invoiceInfo['date'] =
                $this->extract($invoice, './inv:invoiceHeader/inv:date');

            $invoiceInfo['dateDue'] =
                $this->extract($invoice, './inv:invoiceHeader/inv:dateDue');

            $invoiceInfo['text'] =
                $this->extract($invoice, './inv:invoiceHeader/inv:text');

            // Calculate total amount
            $priceNone =
                (float)$this->extract($invoice, './inv:invoiceSummary/inv:homeCurrency/typ:priceNone', 0);
            $priceLowSum =
                (float)$this->extract($invoice, './inv:invoiceSummary/inv:homeCurrency/typ:priceLowSum', 0);
            $priceHighSum =
                (float)$this->extract($invoice, './inv:invoiceSummary/inv:homeCurrency/typ:priceHighSum', 0);

            $invoiceInfo['totalAmount'] = $priceNone + $priceLowSum + $priceHighSum;

            // Remaining debt
            $invoiceInfo['remainingDebt'] =
                (float)$this->extract($invoice, './inv:invoiceHeader/inv:liquidation/typ:amountHome', 0);

            // Liquidation date
            $invoiceInfo['liquidationDate'] =
                $this->extract($invoice, './inv:invoiceHeader/inv:liquidation/typ:date');

            $invoicesData[] = $invoiceInfo;
        }

        return $invoicesData;
    }

    /**
     * Helper method to extract a single value via XPath.
     *
     * @param \SimpleXMLElement $xml
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    private function extract(SimpleXMLElement $xml, string $path, mixed $default = null): mixed
    {
        $nodes = $xml->xpath($path);

        return $nodes && isset($nodes[0]) ? (string)$nodes[0] : $default;
    }
}
