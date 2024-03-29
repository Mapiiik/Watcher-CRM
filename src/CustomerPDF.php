<?php
declare(strict_types=1);

namespace App;

use App\Model\Entity\Customer;
use TCPDF;

//set image path for TCPDF
define('K_PATH_IMAGES', dirname(__DIR__) . DS . 'webroot' . DS . 'legacy' . DS . 'images' . DS);

class CustomerPDF extends TCPDF
{
    /**
     * @inheritDoc
     */
    public function cell(
        mixed $w,
        mixed $h = 0,
        mixed $txt = '',
        mixed $border = 0,
        mixed $ln = 0,
        mixed $align = '',
        mixed $fill = false,
        mixed $link = '',
        mixed $stretch = 0,
        mixed $ignore_min_height = false,
        mixed $calign = 'T',
        mixed $valign = ''
    ): void {
        $valign = $valign == '' ? ($border == 0 ? 'T' : 'M') : $valign;
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
    }

    /**
     * generate PDF document - GDPR agreement
     *
     * @param \App\Model\Entity\Customer $customer Customer with all related data
     * @param string $type Type of requested document
     * @param bool $signed Create signed document?
     * @return void
     */
    public function generateGDPRAgreement(Customer $customer, string $type = 'gdpr-new', bool $signed = false): void
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        $this->SetFont('DejaVuSerif', 'BI', 8);

        $this->SetFont('DejaVuSerif', 'B', 18);
        $this->Cell(187, 6, 'SOUHLAS', align: 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', 12);
        $this->Cell(187, 2, 'se zpracováním osobních údajů', align: 'C');
        $this->Ln(3);

        $this->Ln(4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(0.5);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(62, 4, 'nový / změna:', align: 'C');
        $this->Cell(62, 4, 'číslo souhlasu:', align: 'C');
        $this->Cell(62, 4, 'doba trvání souhlasu:', align: 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', 8);
        switch ($type) {
            case 'gdpr-new':
                $this->Cell(62, 4, 'nový', align: 'C');
                $this->Cell(62, 4, $customer->number, align: 'C');
                $this->Cell(62, 4, 'na dobu neurčitou', align: 'C');
                break;
            case 'gdpr-change':
                $this->Cell(62, 4, 'změna', align: 'C');
                $this->Cell(62, 4, $customer->number, align: 'C');
                $this->Cell(62, 4, 'na dobu neurčitou', align: 'C');
                break;
        }
        $this->Ln();

        $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(3);

        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 2, iconv('UTF-8', 'UTF-8', 'mezi'), align: 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(45, 4, iconv('UTF-8', 'UTF-8', 'Správcem:'));
        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->Ln(1);
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4, 'NETAIR, s.r.o.');
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4);
        $this->Cell(15, 4, 'tel:');
        $this->Cell(40, 4, '+420 488 572 050');
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, 'Jablonec nad Jizerou 299');
        $this->Cell(20, 4);
        $this->Cell(10, 4, 'IČ:');
        $this->Cell(40, 4, '27496139');
        $this->Cell(15, 4, 'mobil:');
        $this->Cell(40, 4, '+420 604 553 444');
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, '512 43 Jablonec nad Jizerou');
        $this->Cell(20, 4);
        $this->Cell(10, 4, 'DIČ:');
        $this->Cell(40, 4, 'CZ27496139');
        $this->Cell(15, 4, 'e-mail:');
        $this->Cell(40, 4, 'mail@netair.cz');
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, 'zastoupeným Marko Jujnovićem, jednatelem', align: 'L');
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, 'zapsaným v obchodním rejstříku vedeném u Krajského soudu v Hradci Králové, oddíl C, vložka 22450.', align: 'L');

        $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(3);

        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 4, 'a', align: 'C');
        $this->Ln();
        $this->Cell(30, 4, 'Uživatelem:');

        $this->SetFont('DejaVuSerif', '', 8);
        if (is_null($customer->ic)) {
            $this->Cell(60, 4, 'fyzická osoba nepodnikající');
        } elseif (is_null($customer->billing_address->company)) {
            $this->Cell(60, 4, 'fyzická osoba podnikající');
        } else {
            $this->Cell(60, 4, 'právnická osoba');
        }

        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(0.5);

        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(90, 4, 'Osobní údaje:');
        $this->Cell(90, 4, 'Obchodní údaje:');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'jméno:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->billing_address->full_name);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'firma:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $customer->billing_address->company ?? 'X', align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'datum narození:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, h($customer->date_of_birth));

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'IČ:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->ic ?? 'X');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'číslo OP:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->identity_card_number);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'DIČ:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->dic ?? 'X');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'tel:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(160, 4, $customer->phone, align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, 'e-mail:', align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(160, 4, $customer->email, align: 'L');

        foreach ($customer->addresses as $address) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, $address->type->label() . ': ');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $address->full_address, align: 'L');
        }

        $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->SetFont('DejaVuSerif', '', 7);
        $this->Write(3, '
Prohlášení Správce:

    Správce prohlašuje, že bude zpracovávat osobní údaje v rozsahu nezbytném pro naplnění níže stanovených účelů, plnění smlouvy, plnění zákonných povinností a ochrany oprávněných zájmů. Zaměstnanci Správce nebo jiné fyzické osoby, které zpracovávají osobní údaje na základě smlouvy se Správcem a další osoby jsou povinni zachovávat mlčenlivost o osobních údajích, a to i po skončení pracovního poměru nebo prací.

Já, níže podepsaný:

1. Uděluji tímto souhlas se zpracováním osobních údajů Správcem, pro účely stanovené níže. Tento souhlas uděluji pro následující údaje:
Jméno, příjmení, emailová adresa, telefonní číslo, adresa trvalého pobytu, adresa místa připojení, fakturační adresa, korespondenční adresa, datum narození, IP adresa, typ a objem poskytnutých služeb, daňové a účetní doklady

2. Tento souhlas uděluji na dobu neurčitou a můžu ho kdykoli vzít zpět, a to stejným způsobem, jakým jsem jej udělil nebo pomocí Uživatelského portálu Správce.

3. Zpracování osobních údajů je prováděno Správcem.

4. Beru na vědomí, že podle zákona o ochraně osobních údajů mám právo:
    a) vzít souhlas kdykoliv zpět
    b) požadovat po Správci informaci, jaké moje osobní údaje zpracovává
    c) požadovat po Správci vysvětlení ohledně zpracování osobních údajů
    d) vyžádat si u Správce přístup k těmto údajům a tyto nechat aktualizovat nebo opravit
    e) požadovat po Správci výmaz těchto osobních údajů, pokud Správce neprokáže oprávněné důvody pro zpracování těchto osobních údajů
    f) v případě pochybností o dodržování povinností souvisejících se zpracováním osobních údajů obrátit se na Správce nebo na Úřad pro ochranu osobních údajů
');

        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Write(3, '
▢ souhlasím se zasíláním veškeré korespondence spojené s měsíčním vyúčtováním *
▢ souhlasím se zasíláním informací o odstávkách a poruchách *
▢ souhlasím se zasíláním obchodních sdělení *

* zašrtněte prosím jaké typy zpráv chcete dostávat
');

        // SIGNS
        $this->SetFont('DejaVuSerif', '', 8);
        if ($this->GetY() > 240) {
            $this->AddPage();
        }
        $this->Ln();
        $this->Ln();

        $this->Cell(90, 4);
        $this->Cell(90, 4, 'Datum podpisu: ____________________', align: 'C');

        $this->Ln(20);

        $this->Cell(90, 4);
        $this->Cell(90, 4, '......................................................', align: 'C');
        $this->Ln();
        $this->Cell(90, 4);
        $this->Cell(90, 4, 'Uživatel', align: 'C');
        $this->Ln();

        $this->Close();
    }
}
