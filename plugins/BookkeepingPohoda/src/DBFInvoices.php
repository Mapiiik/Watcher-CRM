<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace BookkeepingPohoda;

/**
 * Description of DBFInvoices
 *
 * @author mapik
 */
class DBFInvoices {
	var $dbf;
	var $charset;
	var $structure;

	function __construct()
	{
		//Set charset
		$this->charset = "CP852";
		//DB structure
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Cislo"),		"C", "10");     //cislo faktury
		$this->structure[] = array(iconv("UTF-8", $this->charset, "VarSym"),		"C", "20");	//variabilni symbol
		$this->structure[] = array(iconv("UTF-8", $this->charset, "SText"),		"C", "240");    //obecny text
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Datum"),		"D");           //datum vystaveni
		$this->structure[] = array(iconv("UTF-8", $this->charset, "DatUcP"),		"D");		//posledni den v mesici
		$this->structure[] = array(iconv("UTF-8", $this->charset, "DatSplat"),		"D");		//datum splatnosti
		$this->structure[] = array(iconv("UTF-8", $this->charset, "DatZdPln"),		"D");		//posledni den v mesici

                $this->structure[] = array(iconv("UTF-8", $this->charset, "Kc0"),		"N", 8, 2);     //vždy 0
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Kc1"),		"N", 8, 2);	//vždy 0
		$this->structure[] = array(iconv("UTF-8", $this->charset, "KcDPH1"),		"N", 8, 2);	//vždy 0
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Kc2"),		"N", 8, 2);	//cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
		$this->structure[] = array(iconv("UTF-8", $this->charset, "KcDPH2"),		"N", 8, 2);	//DPH = cena * 0,1597 (na 2 des mista)
		$this->structure[] = array(iconv("UTF-8", $this->charset, "KcZaloha"),		"N", 8, 2);	//vždy 0
		$this->structure[] = array(iconv("UTF-8", $this->charset, "KcCelkem"),		"N", 8, 2);	//cena celkem
		$this->structure[] = array(iconv("UTF-8", $this->charset, "KcLikv"),            "N", 8, 2);     //cena celkem
		$this->structure[] = array(iconv("UTF-8", $this->charset, "KcU"),		"N", 8, 2);	//vždy 0
		$this->structure[] = array(iconv("UTF-8", $this->charset, "KcZaokr"),		"N", 8, 2);	//vždy 0

		$this->structure[] = array(iconv("UTF-8", $this->charset, "Firma"),		"C", "96");	//název firmy
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Utvar"),		"C", "32");	//provozovna
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Jmeno"),		"C", "32");	//jméno + příjmení
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Ulice"),		"C", "32");	//ulice
		$this->structure[] = array(iconv("UTF-8", $this->charset, "PSC"),		"C", "7");	//PSČ
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Obec"),		"C", "35");	//Obec
		$this->structure[] = array(iconv("UTF-8", $this->charset, "ICO"),		"C", "12");	//IČ
		$this->structure[] = array(iconv("UTF-8", $this->charset, "DIC"),		"C", "15");	//DIČ

		$this->structure[] = array(iconv("UTF-8", $this->charset, "KonstSym"),		"C", "4");	//konstantní symbol 0308

		$this->structure[] = array(iconv("UTF-8", $this->charset, "Pozn"),		"C", "240");	//poznámka
		$this->structure[] = array(iconv("UTF-8", $this->charset, "Pozn2"), 		"C", "240");	//interní poznámka
	}

	function CreateDBF($dbf_filename)
	{
		$this->dbf = dbase_create($dbf_filename, $this->structure);
		if (!($this->dbf))
		{
			die ("Errror when creating dBase file !!!!");
		}
	}

	function CloseDBF()
	{
		dbase_close($this->dbf);
	}

	function AddRecord($invoice, $reverse_charge = false)
	{
                $totalcost = $invoice->total;
            
		//START add record to dBase file
                $dph = round($totalcost - ($totalcost / (1 + env('VAT_RATE'))), 2);

		$data[] = $invoice->number;							//cislo faktury
		$data[] = $invoice->variable_symbol;						//variabilni symbol
		$data[] = $invoice->text;							//obecny text
		$data[] = $invoice->creation_date->i18nFormat('yyyyMMdd');			//datum vystaveni
		$data[] = $invoice->creation_date->i18nFormat('yyyyMMdd');			//posledni den v mesici
		$data[] = $invoice->due_date->i18nFormat('yyyyMMdd');				//datum splatnosti
		$data[] = $invoice->creation_date->i18nFormat('yyyyMMdd');			//posledni den v mesici

                if ($reverse_charge)
                {
                    $data[] = 0;											//vždy 0
                    $data[] = 0;											//vždy 0
                    $data[] = 0;											//vždy 0
                    $data[] = $totalcost - $dph;    //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
                    $data[] = 0;											//vždy 0
                    $data[] = 0;											//vždy 0
                    $data[] = $totalcost - $dph;    //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
                    $data[] = $totalcost - $dph;    //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
                    $data[] = 0;											//vždy 0
                    $data[] = 0;											//vždy 0
                }
                else
                {
                    $data[] = 0;											//vždy 0
                    $data[] = 0;											//vždy 0
                    $data[] = 0;											//vždy 0
                    $data[] = $totalcost - $dph;    //cena bez DPH = cena - cena * 0,1597 (na 2 des mista)
                    $data[] = $dph;                 //DPH = cena * 0,1597 (na 2 des mista)
                    $data[] = 0;											//vždy 0
                    $data[] = $totalcost;                                                                               //cena celkem
                    $data[] = $totalcost;                                                                               //cena celkem
                    $data[] = 0;											//vždy 0
                    $data[] = 0;											//vždy 0
                }

		$data[] = $invoice->customer->billing_address->company;							//název firmy
		$data[] = null;                                                                                         //provozovna
		$data[] = $invoice->customer->billing_address->full_name;						//jméno + příjmení
		$data[] = $invoice->customer->billing_address->street_and_number;					//ulice
		$data[] = $invoice->customer->billing_address->zip;							//PSČ
		$data[] = $invoice->customer->billing_address->city;							//Obec
		$data[] = $invoice->customer->ic;                                                                       //IČ
		$data[] = $invoice->customer->dic;                                                                      //DIČ

		$data[] = "0308";                                                                                       //konstantní symbol 0308

		$data[] = $invoice->note;                                                                               //poznámka
		$data[] = $invoice->internal_note;                                                                      //interní poznámka

		foreach ($data as $key => $value)
		{
			if (is_string($value))
			{
				$xdata[] = iconv("UTF-8", $this->charset, $value);
			}
			else
			{
				$xdata[] = $value;
			}
		}
		dbase_add_record($this->dbf, $xdata);
		unset($data);
		unset($xdata);
		//STOP add record to dBase file
	}
}
?>