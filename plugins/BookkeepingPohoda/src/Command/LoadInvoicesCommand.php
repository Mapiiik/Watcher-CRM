<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Exception;
use InvalidArgumentException;
use Riesenia\Pohoda;
use RuntimeException;
use SimpleXMLElement;

/**
 * LoadInvoices command.
 */
class LoadInvoicesCommand extends Command
{
    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Loading of invoices via POHODA mSERVER.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addOption('last_changes', [
                'help' => __d(
                    'bookkeeping_pohoda',
                    'Override the saved date and time of the last synchronisation.'
                        . ' Only invoices changed after this date and time of change will be retrieved.'
                        . ' Format: YYYY-MM-DD HH:MM:SS (e.g., 2024-10-28 15:45:00).'
                        . ' If not provided, the date from the last successful synchronization will be used.',
                ),
                'default' => null,
            ]);
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $username = env('POHODA_USERNAME', '');
        $password = env('POHODA_PASSWORD', '');
        $url = env('POHODA_MSERVER_URL', 'http://localhost:44444');

        $timestamp = new DateTime(); // now()
        $timeout = 3600; // Timeout for the HTTP request

        $lastChanges = $this->getSynchronizationDateTime($args, $io);

        if ($lastChanges === null) {
            // Error handling is already done in getSynchronizationDateTime()
            return static::CODE_ERROR;
        }

        // Synchronization logic using $lastChanges.
        $io->info(__d(
            'bookkeeping_pohoda',
            'Using last changes time: {0}',
            [$lastChanges->format('Y-m-d H:i:s')],
        ));

        $http = new Client([
            'headers' => [
                'STW-Application' => 'Watcher CRM',
                'STW-Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
            ],
            'timeout' => $timeout,
        ]);

        try {
            $response = $http->post(
                $url . '/xml',
                $this->generateXMLRequest($lastChanges),
            );
        } catch (Exception $e) {
            $io->error(__d(
                'bookkeeping_pohoda',
                'Error connecting to server: {0}',
                [$e->getMessage()],
            ));

            return static::CODE_ERROR;
        }

        if (!$response->isOk()) {
            // Error handling if no response
            $io->error(__d(
                'bookkeeping_pohoda',
                'Invalid response from the server ({0})',
                [$response->getReasonPhrase()],
            ));

            return static::CODE_ERROR;
        }

        // Get XML as a SimpleXMLElement object
        $xml = $response->getXml();

        if ($xml === null) {
            // Handle error if the response cannot be parsed as valid XML
            $io->error(__d(
                'bookkeeping_pohoda',
                'Invalid XML response',
            ));

            return static::CODE_ERROR;
        }

        // Access root attributes
        $id = isset($xml->attributes()->id) ? (string)$xml->attributes()->id : null;
        $state = isset($xml->attributes()->state) ? (string)$xml->attributes()->state : null;
        $note = isset($xml->attributes()->note) ? (string)$xml->attributes()->note : null;

        if ($state !== 'ok') {
            $io->error(__d(
                'bookkeeping_pohoda',
                'The server returned an XML error response (ID: {0}, STATE: {1}, NOTE: {2})',
                [($id ?? 'N/A'), ($state ?? 'N/A'), ($note ?? 'N/A')],
            ));

            return static::CODE_ERROR;
        }

        $io->info(__d(
            'bookkeeping_pohoda',
            'The server returned a valid XML response (ID: {0}, STATE: {1}, NOTE: {2})',
            [($id ?? 'N/A'), $state, ($note ?? 'N/A')],
        ));

        // Retrieve invoices from XML
        $invoicesData = $this->parseInvoicesFromXML($xml);

        // Output of extracted data if debugging is enabled
        if (Configure::read('debug')) {
            foreach ($invoicesData as $invoiceData) {
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Invoice Number: {0}',
                    [$invoiceData['numberRequested'] ?? 'N/A'],
                ));
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Variable Symbol: {0}',
                    [$invoiceData['symVar'] ?? 'N/A'],
                ));
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Issue Date: {0}',
                    [$invoiceData['date'] ?? 'N/A'],
                ));
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Due Date: {0}',
                    [$invoiceData['dateDue'] ?? 'N/A'],
                ));
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Text: {0}',
                    [$invoiceData['text'] ?? 'N/A'],
                ));
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Total Amount: {0}',
                    [$invoiceData['totalAmount'] ?? 'N/A'],
                ));
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Liquidation Date: {0}',
                    [$invoiceData['liquidationDate'] ?? 'N/A'],
                ));
                $io->out(__d(
                    'bookkeeping_pohoda',
                    'Remaining Debt: {0}',
                    [$invoiceData['remainingDebt'] ?? 'N/A'],
                ));
                $io->out('--------------------');
            }
        }

        // Update invoice data in the local database
        $this->updateInvoices($invoicesData, $io);

        // Save the new time of the last synchronization to a file.
        $this->saveLastSynchronizationTime($timestamp);

        return static::CODE_SUCCESS;
    }

    /**
     * Generates XML request to export issued invoices.
     *
     * @param \Cake\I18n\DateTime $lastChanges DateTime object representing the last changes timestamp.
     * @return string The generated XML request string.
     */
    private function generateXMLRequest(DateTime $lastChanges): string
    {
        Pohoda::$encoding = 'UTF-8'; // Set encoding for Pohoda library

        $pohoda = new Pohoda(env('POHODA_COMPANY_ID', '00000000'));
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

    /**
     * Parse invoices from XML
     */
    private function parseInvoicesFromXML(SimpleXMLElement $xml): array
    {
        // Register namespaces (ESSENTIAL!)
        $xml->registerXPathNamespace('rsp', 'http://www.stormware.cz/schema/version_2/response.xsd');
        $xml->registerXPathNamespace('lst', 'http://www.stormware.cz/schema/version_2/list.xsd');
        $xml->registerXPathNamespace('inv', 'http://www.stormware.cz/schema/version_2/invoice.xsd');
        $xml->registerXPathNamespace('typ', 'http://www.stormware.cz/schema/version_2/type.xsd');

        $invoices = $xml->xpath('//lst:invoice');
        $invoicesData = [];

        foreach ($invoices as $invoice) {
            $invoiceInfo = [];

            $numberRequestedNodes = $invoice->xpath('./inv:invoiceHeader/inv:number/typ:numberRequested');
            $invoiceInfo['numberRequested'] = $numberRequestedNodes ? (string)$numberRequestedNodes[0] : null;

            $symVarNodes = $invoice->xpath('./inv:invoiceHeader/inv:symVar');
            $invoiceInfo['symVar'] = $symVarNodes ? (string)$symVarNodes[0] : null;

            $dateNodes = $invoice->xpath('./inv:invoiceHeader/inv:date');
            $invoiceInfo['date'] = $dateNodes ? (string)$dateNodes[0] : null;

            $dateDueNodes = $invoice->xpath('./inv:invoiceHeader/inv:dateDue');
            $invoiceInfo['dateDue'] = $dateDueNodes ? (string)$dateDueNodes[0] : null;

            $textNodes = $invoice->xpath('./inv:invoiceHeader/inv:text');
            $invoiceInfo['text'] = $textNodes ? (string)$textNodes[0] : null;

            // Calculate total amount (sum of priceNone, priceLowSum, and priceHighSum)
            $priceNoneNodes = $invoice->xpath('./inv:invoiceSummary/inv:homeCurrency/typ:priceNone');
            $priceLowSumNodes = $invoice->xpath('./inv:invoiceSummary/inv:homeCurrency/typ:priceLowSum');
            $priceHighSumNodes = $invoice->xpath('./inv:invoiceSummary/inv:homeCurrency/typ:priceHighSum');

            $priceNone = $priceNoneNodes ? (float)$priceNoneNodes[0] : 0;
            $priceLowSum = $priceLowSumNodes ? (float)$priceLowSumNodes[0] : 0;
            $priceHighSum = $priceHighSumNodes ? (float)$priceHighSumNodes[0] : 0;

            $invoiceInfo['totalAmount'] = $priceNone + $priceLowSum + $priceHighSum; // Correct calculation

            $liquidationAmountNodes = $invoice->xpath('./inv:invoiceHeader/inv:liquidation/typ:amountHome');
            $invoiceInfo['remainingDebt'] = $liquidationAmountNodes ? (float)$liquidationAmountNodes[0] : 0; // Correct interpretation of remaining debt

            $liquidationDateNodes = $invoice->xpath('./inv:invoiceHeader/inv:liquidation/typ:date');
            $invoiceInfo['liquidationDate'] = $liquidationDateNodes ? (string)$liquidationDateNodes[0] : null;

            $invoicesData[] = $invoiceInfo;
        }

        return $invoicesData;
    }

    /**
     * Update invoices
     */
    private function updateInvoices(iterable $invoicesData, ConsoleIo $io): bool
    {
        /** @var \BookkeepingPohoda\Model\Table\InvoicesTable $invoicesTable */
        $invoicesTable = $this->fetchTable('BookkeepingPohoda.Invoices');

        // counters
        $imported = 0;
        $created = 0;
        $modified = 0;

        // load customer IDs
        $customerIds = $invoicesTable->Customers
        ->find(
            'list',
            keyField: 'nid',
            valueField: 'id',
        )
        ->toArray();

        foreach ($invoicesData as $invoiceData) {
            $imported++;

            if (
                !(
                    isset($invoiceData['numberRequested'])
                    && isset($invoiceData['symVar'])
                    && isset($invoiceData['date'])
                    && isset($invoiceData['dateDue'])
                    && isset($invoiceData['text'])
                    && isset($invoiceData['totalAmount'])
                    && isset($invoiceData['remainingDebt'])
                )
            ) {
                $io->error(__d(
                    'bookkeeping_pohoda',
                    'The import file is missing some required columns.',
                ));

                return false;
            }

            if (
                ((int)env('CUSTOMER_SERIES', '0') < (int)$invoiceData['symVar']) &&
                ((int)$invoiceData['symVar'] < (int)env('CUSTOMER_SERIES', '0') + 50000)
            ) {
                /** @var \BookkeepingPohoda\Model\Entity\Invoice $invoice */
                $invoice =
                    $invoicesTable->find()->where(['number' => $invoiceData['numberRequested']])->first()
                    ??
                    $invoicesTable->newEntity(['number' => $invoiceData['numberRequested']]);

                $invoice->customer_id =
                    $customerIds[(int)$invoiceData['symVar'] - (int)env('CUSTOMER_SERIES', '0')] ?? null;

                $invoice->variable_symbol = (int)$invoiceData['symVar'];
                $invoice->creation_date = $invoiceData['date'];
                $invoice->due_date = $invoiceData['dateDue'];
                $invoice->text = $invoiceData['text'];
                $invoice->total = $invoiceData['totalAmount'];
                $invoice->debt = $invoiceData['remainingDebt'];
                $invoice->payment_date = $invoiceData['liquidationDate'] <> '' ? $invoiceData['liquidationDate'] : null;

                if ($invoice->isNew()) {
                    $created++;
                } else {
                    $modified++;
                }

                $invoicesTable->saveOrFail($invoice);

                if ($invoice->hasErrors()) {
                    $io->error(__d(
                        'bookkeeping_pohoda',
                        'Invoice {0} could not be loaded.',
                        $invoice->number,
                    ));
                }
            }
        }

        $io->success(__d(
            'bookkeeping_pohoda',
            'Successfully imported {0} invoices. Created {1}, modified {2} and skipped {3} records.',
            $imported,
            $created,
            $modified,
            $imported - $created - $modified,
        ));

        return true;
    }

    /**
     * Gets the DateTime object to use for synchronization.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console IO object.
     * @return \Cake\I18n\DateTime|null The DateTime object or null on error.
     */
    private function getSynchronizationDateTime(
        Arguments $args,
        ConsoleIo $io,
    ): ?DateTime {
        $lastChangesString = $args->getOption('last_changes');

        if ($lastChangesString !== null) {
            try {
                $lastChanges = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $lastChangesString,
                );
            } catch (InvalidArgumentException $e) {
                $io->error(__d(
                    'bookkeeping_pohoda',
                    'Invalid date and time format. Use YYYY-MM-DD HH:MM:SS, Error: {0}',
                    [$e->getMessage()],
                ));

                return null;
            }
        } else {
            // Load last synchronization time from file.
            $lastChanges = $this->loadLastSynchronizationTime();
            if ($lastChanges === null) {
                $io->warning(__d(
                    'bookkeeping_pohoda',
                    'No previous synchronization time found. ' .
                    'Using default/initial time.',
                ));
                // Set default/initial time (3 month ago).
                $lastChanges = new DateTime('-3 month');
            }
        }

        return $lastChanges;
    }

    /**
     * Loads the last successful synchronization time from file.
     *
     * @return \Cake\I18n\DateTime|null The last synchronization time or null if not found or an error occurred.
     */
    private function loadLastSynchronizationTime(): ?DateTime
    {
        $filename = env('DATA_ROOT', ROOT . DS . 'data') . DS . 'invoices' . DS . 'last_sync.txt';

        if (!file_exists($filename)) {
            return null; // Early return if file does not exist
        }

        $time = file_get_contents($filename);
        if ($time === false) { // Check if file_get_contents was successful
            Log::error(__d(
                'bookkeeping_pohoda',
                'Error reading last sync time from file: {0}',
                [$filename],
            ));

            return null;
        }

        try {
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $time);
        } catch (InvalidArgumentException $e) {
            // Log the exception for debugging purposes
            Log::error(__d(
                'bookkeeping_pohoda',
                'Error parsing last sync time from file: {0}',
                [$e->getMessage()],
            ));

            return null;
        }

        return $dateTime;
    }

    /**
     * Saves the last synchronization time to file.
     *
     * @param \Cake\I18n\DateTime $dateTime The last synchronization time.
     * @return void
     */
    private function saveLastSynchronizationTime(DateTime $dateTime): void
    {
        $filename = env('DATA_ROOT', ROOT . DS . 'data') . DS . 'invoices' . DS . 'last_sync.txt';

        $result = file_put_contents($filename, $dateTime->format('Y-m-d H:i:s'));
        if ($result === false) {
            Log::error(__d(
                'bookkeeping_pohoda',
                'Error saving last sync time to file: {0}',
                [$filename],
            ));
        }
    }
}
