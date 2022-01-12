<?php
//set image path for TCPDF
define('K_PATH_IMAGES', WWW_ROOT . 'legacy' . DS . 'images' . DS);

// define date format
\Cake\I18n\FrozenDate::setToStringFormat('dd.MM.yyyy');

class CustomerPDF extends TCPDF
{
    function contractDurationBeforeX ($duration)
    {
        if ($duration <= 0) {
            die(WRONG_DURATION);
        } else {
            if ($duration < 2) {
                return $duration . ' měsíce';
            }
            return $duration . ' měsíců';
        }
    }

    function contractDurationX ($duration)
    {
        if ($duration <= 0) {
            return 'na dobu neurčitou';
        } else {
            if ($duration < 2) {
                return 'na dobu neurčitou s minimální dobou plnění v trvání ' . $duration . ' měsíce';
            }
            return 'na dobu neurčitou s minimální dobou plnění v trvání ' . $duration . ' měsíců';
        }
    }

    function GenerateGDPRAgreement($customer, $type = 'gdpr-new', $signed, $address_types = null)
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png',10, 5, 28);

        $this->SetFont('DejaVuSerif', 'BI', '8');

        $this->SetFont('DejaVuSerif','B', '18');
        $this->Cell(187, 6, 'SOUHLAS', '', '', 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', '12');
        $this->Cell(187, 2, 'se zpracováním osobních údajů','','','C');
        $this->Ln(3);

        $this->Ln(4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(62, 4, 'nový / změna:', '', '', 'C');
        $this->Cell(62, 4, 'číslo souhlasu:', '', '', 'C');
        $this->Cell(62, 4, 'doba trvání souhlasu:', '', '', 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', '8');
        switch ($type) {
        case 'gdpr-new':
            $this->Cell(62, 4, 'nový', '', '', 'C');
            $this->Cell(62, 4, $customer->number, '', '', 'C');
            $this->Cell(62, 4, 'na dobu neurčitou', '', '', 'C');
                break;
        case 'gdpr-change':
            $this->Cell(62, 4, 'změna', '', '', 'C');
            $this->Cell(62, 4, $customer->number, '', '', 'C');
            $this->Cell(62, 4, 'na dobu neurčitou', '', '', 'C');
            break;
        };
        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(3);

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'mezi'),'','','C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'Správcem:'));
        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->Ln(1);
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30, 4, '', '', '', 'R');
        $this->Cell(40, 4, 'NETAIR, s.r.o.');
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, '', '', '', 'R');
        $this->Cell(40, 4, '');
        $this->Cell(15, 4, 'tel:', '', '', 'L');
        $this->Cell(40, 4, '+420 488 572 050');
        $this->Ln();

        $this->Cell(30, 4, '', '', '', 'R');
        $this->Cell(40, 4, 'Jablonec nad Jizerou 299');
        $this->Cell(20, 4, '', '', '', 'R');
        $this->Cell(10, 4, 'IČ:');
        $this->Cell(40, 4, '27496139');
        $this->Cell(15, 4, 'mobil:', '', '', 'L');
        $this->Cell(40, 4, '+420 604 553 444');
        $this->Ln();

        $this->Cell(30, 4, '', '', '', 'R');
        $this->Cell(40, 4, '512 43 Jablonec nad Jizerou');
        $this->Cell(20, 4, '', '', '', 'R');
        $this->Cell(10, 4, 'DIČ:');
        $this->Cell(40, 4, 'CZ27496139');
        $this->Cell(15, 4, 'e-mail:', '', '', 'L');
        $this->Cell(40, 4, 'mail@netair.cz');
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, '');
        $this->MultiCell(157, 4, 'zastoupeným Marko Jujnovićem, jednatelem', '', 'L');
        $this->Cell(30, 4, '');
        $this->MultiCell(157, 4, 'zapsaným v obchodním rejstříku vedeném u Krajského soudu v Hradci Králové, oddíl C, vložka 22450.', '', 'L');

        $this->Line($this->GetX() + 4,$this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(3);

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(187, 4, 'a', '', '', 'C');
        $this->Ln();
        $this->Cell(30, 4, 'Uživatelem:');

        $this->SetFont('DejaVuSerif', '', '8');
        if (is_null($customer->ic))
        {
            $this->Cell(60, 4, 'fyzická osoba nepodnikající');
        }
        else if (is_null($customer->billing_address->company))
        {
            $this->Cell(60, 4, 'fyzická osoba podnikající');
        }
        else
        {
            $this->Cell(60, 4, 'právnická osoba');
        }

        $this->Ln();
        
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(90, 4, 'Osobní údaje:', '', '', 'L');
        $this->Cell(90, 4, 'Obchodní údaje:', '', '', 'L');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'jméno:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60, 4, $customer->billing_address->full_name);
        
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'firma:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $customer->billing_address->company, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'datum narození:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60, 4, $customer->date_of_birth);
        
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'IČ:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60, 4, $customer->ic);
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'číslo OP:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60, 4, $customer->identity_card_number);
        
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'DIČ:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60, 4, $customer->dic);
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'tel:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(160, 4, $customer->phone, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'e-mail:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(160, 4, $customer->email, '', 'L');

        foreach ($customer->addresses as $address)
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, $address_types[$address->type] . ': ', '' , '' , 'L');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $address->full_address, '', 'L');                    
        }

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->SetFont('DejaVuSerif', '', '7');
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

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Write(3, '
▢ souhlasím se zasíláním veškeré korespondence spojené s měsíčním vyúčtováním *
▢ souhlasím se zasíláním informací o odstávkách a poruchách *
▢ souhlasím se zasíláním obchodních sdělení *

* zašrtněte prosím jaké typy zpráv chcete dostávat
');

        // SIGNS
        $this->SetFont('DejaVuSerif', '', '8');
        if ($this->GetY() > 240) $this->AddPage();
        $this->Ln();
        $this->Ln();
/*        
        if ($signed) 
            $this->Cell(90, 4, 'Datum podpisu: ' . new \Cake\I18n\FrozenDate(), '', '', 'C');
        else
            $this->Cell(90, 4, 'Datum podpisu: ____________________', '', '', 'C');
*/
        $this->Cell(90, 4);
        $this->Cell(90, 4, 'Datum podpisu: ____________________', '', '', 'C');

        $this->Ln(20);

        $this->Cell(90, 4);
        $this->Cell(90, 4, '......................................................', '', '', 'C');
        $this->Ln();
        $this->Cell(90, 4);
        $this->Cell(90, 4, 'Uživatel', '', '', 'C');
        $this->Ln();

        if ($signed) $this->Image(K_PATH_IMAGES . 'signature.png', 38, $this->GetY() - 19, 35);

        $this->Close();
    }
}

if (isset($query['signed']) && $query['signed'] == 1) {
    $signed = true;
} else {
    $signed = false;
}

switch ($type) {
case 'gdpr-new':
case 'gdpr-change':
    //Generate PDF
    $pdf = new CustomerPDF('P', 'mm', 'A4');
    $pdf->GenerateGDPRAgreement($customer, $type, $signed, $address_types);
    $pdf->Output($customer->number . '_' . $type . '_' . date('Y-m-d') . '.pdf', 'I');
    break;
default:
    exit;
}
?>