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
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Cislo'), 'C', 10]; //cislo faktury
        $this->structure[] = [iconv('UTF-8', $this->charset, 'VarSym'), 'C', 20]; //variabilni symbol
        $this->structure[] = [iconv('UTF-8', $this->charset, 'SText'), 'C', 240]; //obecny text
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Datum'), 'D']; //datum vystaveni
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DatUcP'), 'D']; //posledni den v mesici
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DatSplat'), 'D']; //datum splatnosti
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DatZdPln'), 'D']; //posledni den v mesici

        $this->structure[] = [iconv('UTF-8', $this->charset, 'Kc0'), 'N', 8, 2]; //vždy 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Kc1'), 'N', 8, 2]; //vždy 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcDPH1'), 'N', 8, 2]; //vždy 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Kc2'), 'N', 8, 2]; //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcDPH2'), 'N', 8, 2]; //DPH = cena * 0,1597 (na 2 des mista)
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcZaloha'), 'N', 8, 2]; //vždy 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcCelkem'), 'N', 8, 2]; //cena celkem
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcLikv'), 'N', 8, 2]; //cena celkem
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcU'), 'N', 8, 2]; //vždy 0
        $this->structure[] = [iconv('UTF-8', $this->charset, 'KcZaokr'), 'N', 8, 2]; //vždy 0

        $this->structure[] = [iconv('UTF-8', $this->charset, 'Firma'), 'C', 96]; //název firmy
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Utvar'), 'C', 32]; //provozovna
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Jmeno'), 'C', 32]; //jméno + příjmení
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Ulice'), 'C', 32]; //ulice
        $this->structure[] = [iconv('UTF-8', $this->charset, 'PSC'), 'C', 7]; //PSČ
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Obec'), 'C', 35]; //Obec
        $this->structure[] = [iconv('UTF-8', $this->charset, 'ICO'), 'C', 12]; //IČ
        $this->structure[] = [iconv('UTF-8', $this->charset, 'DIC'), 'C', 15]; //DIČ

        $this->structure[] = [iconv('UTF-8', $this->charset, 'KonstSym'), 'C', 4]; //konstantní symbol 0308

        $this->structure[] = [iconv('UTF-8', $this->charset, 'Pozn'), 'C', 240]; //poznámka
        $this->structure[] = [iconv('UTF-8', $this->charset, 'Pozn2'), 'C', 240]; //interní poznámka
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

        $data[] = $invoice->number; //cislo faktury
        $data[] = $invoice->variable_symbol; //variabilni symbol
        $data[] = $invoice->text; //obecny text
        $data[] = $invoice->creation_date->i18nFormat('yyyyMMdd'); //datum vystaveni
        $data[] = $invoice->creation_date->i18nFormat('yyyyMMdd'); //posledni den v mesici
        $data[] = $invoice->due_date->i18nFormat('yyyyMMdd'); //datum splatnosti
        $data[] = $invoice->creation_date->i18nFormat('yyyyMMdd'); //posledni den v mesici

        if ($tax_rate->reverse_charge) {
            $data[] = 0; //vždy 0
            $data[] = 0; //vždy 0
            $data[] = 0; //vždy 0
            $data[] = $totalcost - $dph; //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
            $data[] = 0; //vždy 0
            $data[] = 0; //vždy 0
            $data[] = $totalcost - $dph; //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
            $data[] = $totalcost - $dph; //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
            $data[] = 0; //vždy 0
            $data[] = 0; //vždy 0
        } else {
            $data[] = 0; //vždy 0
            $data[] = 0; //vždy 0
            $data[] = 0; //vždy 0
            $data[] = $totalcost - $dph; //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
            $data[] = $dph; //DPH = cena * 0,1597 (na 2 des mista)
            $data[] = 0; //vždy 0
            $data[] = $totalcost; //cena celkem
            $data[] = $totalcost; //cena celkem
            $data[] = 0; //vždy 0
            $data[] = 0; //vždy 0
        }

        $data[] = $invoice->customer->billing_address->company; //název firmy
        $data[] = null; //provozovna
        $data[] = $invoice->customer->billing_address->full_name; //jméno + příjmení
        $data[] = $invoice->customer->billing_address->street_and_number; //ulice
        $data[] = $invoice->customer->billing_address->zip; //PSČ
        $data[] = $invoice->customer->billing_address->city; //Obec
        $data[] = $invoice->customer->ic; //IČ
        $data[] = $invoice->customer->dic; //DIČ

        $data[] = '0308'; //konstantní symbol 0308

        $data[] = $invoice->note; //poznámka
        $data[] = $invoice->internal_note; //interní poznámka

        foreach ($data as $value) {
            if (is_string($value)) {
                $xdata[] = iconv('UTF-8', $this->charset, $value);
            } else {
                $xdata[] = $value;
            }
        }
        dbase_add_record($this->dbf, $xdata);
        unset($data);
        unset($xdata);
 //STOP add record to dBase file
    }
}
