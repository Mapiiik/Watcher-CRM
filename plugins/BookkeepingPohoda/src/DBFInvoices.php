<?php
declare(strict_types=1);

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace BookkeepingPohoda;

use App\Model\Entity\Billing;
use App\Model\Entity\TaxRate;
use BookkeepingPohoda\Model\Entity\Invoice;

/**
 * Description of DBFInvoices
 *
 * @author mapik
 */
class DBFInvoices
{
    /**
     * @var resource|false
     */
    public $dbf;

    /**
     * @var string
     */
    public string $charset;

    /**
     * @var array<array<mixed>>
     */
    private array $structure;

    /**
     * Constructor
     */
    public function __construct()
    {
        //Set charset
        $this->charset = 'CP852';
        //DB structure
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Cislo'), 'C', 10]; //invoice number
        $this->structure[] = [iconv('UTF-8', $this->charset, 'VarSym'), 'C', 20]; //variable symbol
        $this->structure[] = [iconv('UTF-8', $this->charset, 'SText'), 'C', 240]; //general text
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Datum'), 'D']; //issue date
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DatUcP'), 'D']; //last day of the month
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DatSplat'), 'D']; //due date
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DatZdPln'), 'D']; //last day of the month

        $this->structure[] = [iconv('UTF-8', $this->charset, 'Kc0'), 'N', 8, 2]; //always 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Kc1'), 'N', 8, 2]; //always 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcDPH1'), 'N', 8, 2]; //always 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Kc2'), 'N', 8, 2]; //price without VAT = price - price * 0.1597 (2 decimals)
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcDPH2'), 'N', 8, 2]; //VAT = price * 0.1597 (2 decimals)
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcZaloha'), 'N', 8, 2]; //always 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcCelkem'), 'N', 8, 2]; //total price
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcLikv'), 'N', 8, 2]; //total price
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcU'), 'N', 8, 2]; //always 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcZaokr'), 'N', 8, 2]; //always 0

        $this->structure[] = [iconv('UTF-8', $this->charset, 'Firma'), 'C', 96]; //company name
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Utvar'), 'C', 32]; //branch/department
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Jmeno'), 'C', 32]; //first name + surname
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Ulice'), 'C', 32]; //street
        $this->structure[] = [iconv('UTF-8', $this->charset, 'PSC'), 'C', 7]; //postal code
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Obec'), 'C', 35]; //city/town
        $this->structure[] = [iconv('UTF-8', $this->charset, 'ICO'), 'C', 12]; //company ID
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DIC'), 'C', 15]; //VAT ID

        $this->structure[] = [iconv('UTF-8', $this->charset, 'KonstSym'), 'C', 4]; //constant symbol 0308

        $this->structure[] = [iconv('UTF-8', $this->charset, 'Pozn'), 'C', 240]; //note
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Pozn2'), 'C', 240]; //internal note
    }

    /**
     * Create and open new DBF file
     *
     * @param string $path The path of the database. It can be a relative or absolute path to the file where dBase will store your data.
     * @return void No return value
     */
    public function createDBF(string $path): void
    {
        $this->dbf = dbase_create($path, $this->structure);
        if (!$this->dbf) {
            die('Errror when creating dBase file !!!!');
        }
    }

    /**
     * Close opened DBF file
     *
     * @return void No return value
     */
    public function closeDBF(): void
    {
        /** @psalm-suppress UnusedFunctionCall */
        dbase_close($this->dbf);
    }

    /**
     * Add record to opened DBF file
     *
     * @param \BookkeepingPohoda\Model\Entity\Invoice $invoice Invoice
     * @param \App\Model\Entity\TaxRate $tax_rate Tax Rate
     * @return void No return value
     */
    public function addRecord(Invoice $invoice, TaxRate $tax_rate): void
    {
        $totalcost = $invoice->total->toFloat();

        //START add record to dBase file
        $dph = Billing::calcVatFromTotal($invoice->total, $tax_rate->vat_rate)->toFloat();

        $data[] = $invoice->number; //invoice number
        $data[] = $invoice->variable_symbol; //variable symbol
        $data[] = $invoice->text; //general text
        $data[] = $invoice->creation_date->i18nFormat('yyyyMMdd'); //issue date
        $data[] = $invoice->creation_date->i18nFormat('yyyyMMdd'); //last day of the month
        $data[] = $invoice->due_date->i18nFormat('yyyyMMdd'); //due date
        $data[] = $invoice->creation_date->i18nFormat('yyyyMMdd'); //last day of the month

        if ($tax_rate->reverse_charge) {
            $data[] = 0; //always 0
            $data[] = 0; //always 0
            $data[] = 0; //always 0
            $data[] = $totalcost - $dph; //price without VAT = price - price * 0.1597 (2 decimals)
            $data[] = 0; //always 0
            $data[] = 0; //always 0
            $data[] = $totalcost - $dph; //price without VAT = price - price * 0.1597 (2 decimals)
            $data[] = $totalcost - $dph; //price without VAT = price - price * 0.1597 (2 decimals)
            $data[] = 0; //always 0
            $data[] = 0; //always 0
        } else {
            $data[] = 0; //always 0
            $data[] = 0; //always 0
            $data[] = 0; //always 0
            $data[] = $totalcost - $dph; //price without VAT = price - price * 0.1597 (2 decimals)
            $data[] = $dph; //VAT = price * 0.1597 (2 decimals)
            $data[] = 0; //always 0
            $data[] = $totalcost; //total price
            $data[] = $totalcost; //total price
            $data[] = 0; //always 0
            $data[] = 0; //always 0
        }

        $data[] = $invoice->customer->billing_address->company; //company name
        $data[] = null; //branch/department
        $data[] = $invoice->customer->billing_address->full_name; //first name + surname
        $data[] = $invoice->customer->billing_address->street_and_number; //street
        $data[] = $invoice->customer->billing_address->zip; //postal code
        $data[] = $invoice->customer->billing_address->city; //city/town
        $data[] = $invoice->customer->identity_number; //company ID
        $data[] = $invoice->customer->vat_number; //VAT ID

        $data[] = '0308'; //constant symbol 0308

        $data[] = $invoice->note; //note
        $data[] = $invoice->internal_note; //internal note

        foreach ($data as $value) {
            if (is_string($value)) {
                $xdata[] = iconv('UTF-8', $this->charset, $value);
            } else {
                $xdata[] = $value;
            }
        }
        /** @psalm-suppress UnusedFunctionCall */
        dbase_add_record($this->dbf, $xdata);
        unset($data);
        unset($xdata);
        //STOP add record to dBase file
    }
}
