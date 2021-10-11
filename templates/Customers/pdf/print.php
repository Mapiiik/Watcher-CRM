<?php
//set image path for TCPDF
define('K_PATH_IMAGES', WWW_ROOT . 'legacy' . DS . 'images' . DS);

// define date format
\Cake\I18n\FrozenDate::setToStringFormat('dd.MM.YYYY');

class CustomerPDF extends TCPDF
{
    function contractDurationBefore ($duration)
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

    function contractDuration ($duration)
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

    function GenerateGDPRAgreement($data, $type = 1, $signed = false)
    {
            $this->setPrintHeader(false);
            $this->setPrintFooter(false);

            //$address_types variable from config
            global $address_types;

            //$this->Open(); //no in new version

            $this->AddPage();

            $this->Image('../images/logo-contract.png',15,5,40);

            $this->SetFont('DejaVuSerif', 'BI', '8');
            $this->Ln(9);

            $this->Cell(135,4,'');
            $this->Cell(20,4,'tel:','','','R');
            $this->Cell(40,4,'+420 488 572 050');
            $this->Ln();

            $this->Cell(135,4,'');
            $this->Cell(20,4,'mobil:','','','R');
            $this->Cell(40,4,'+420 604 553 444');
            $this->Ln();

            $this->Cell(135,4,'');
            $this->Cell(20,4,'e-mail:','','','R');
            $this->Cell(40,4,'mail@netair.cz');
            $this->Ln();

            $this->Ln(2);

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(0.4);
            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(2);

            $this->SetFont('DejaVuSerif','B', '18');
            $this->Cell(187,6,iconv('UTF-8', 'UTF-8', 'SOUHLAS'),'','','C');
            $this->Ln();

            $this->SetFont('DejaVuSerif','B', '12');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'se zpracováním osobních údajů'),'','','C');
            $this->Ln(3);

            $this->Ln(4);
            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'nový / změna:'),'','','C');
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'číslo souhlasu:'),'','','C');
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'doba trvání souhlasu:'),'','','C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '8');
            switch ($type) {
            case 1:
                    $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'nový'),'','','C');
                    $this->Cell(62,4,iconv('UTF-8', 'UTF-8', $data['varsym']),'','','C');
                    $this->Cell(62,4,iconv('UTF-8', 'UTF-8', $this->contractDuration($data['duration'])),'','','C');
                    break;
            case 2:
                    $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'změna'),'','','C');
                    $this->Cell(62,4,iconv('UTF-8', 'UTF-8', $data['varsym']),'','','C');
                    $this->Cell(62,4,iconv('UTF-8', 'UTF-8', $this->contractDuration($data['duration'])),'','','C');
                    break;
            };
            $this->Ln();

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln();

            $this->Ln(2);
            $this->SetFont('DejaVuSerif','B', '12');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'uzavřený mezi'),'','','C');
            $this->Ln();

            $this->Ln(2);
            $this->SetFont('DejaVuSerif','B', '9');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'Správcem:'));
            $this->Ln();

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30,4,'','','','R');
            $this->Cell(40,4,'NETAIR, s.r.o.');
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,'','','','R');
            $this->Cell(40,4,'');
            $this->Ln();

            $this->Cell(30,4,'','','','R');
            $this->Cell(40,4,iconv('UTF-8', 'UTF-8', 'Jablonec nad Jizerou 299'));
            $this->Cell(20,4,'','','','R');
            $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'IČ:'));
            $this->Cell(40,4,'27496139');
            $this->Ln();

            $this->Cell(30,4,'','','','R');
            $this->Cell(40,4,'512 43 Jablonec nad Jizerou');
            $this->Cell(20,4,'','','','R');
            $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'DIČ:'));
            $this->Cell(40,4,'CZ27496139');
            $this->Ln();

            $this->Ln(4);
            $this->SetFont('DejaVuSerif','', '8');
            $this->Cell(30,4,'');
            $this->Cell(100,4,iconv('UTF-8', 'UTF-8', 'zastoupeným Marko Jujnovićem, jednatelem'));
            $this->Ln();
            $this->Cell(30,4,'');
            $this->Cell(100,4,iconv('UTF-8', 'UTF-8', 'zapsaným v obchodním rejstříku vedeném u Krajského soudu v Hradci Králové, oddíl C, vložka 22450.'));
            $this->Ln();
            $this->Ln();

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

            $this->SetFont('DejaVuSerif','B', '9');
            $this->Ln();
            $this->Cell(187,4,iconv('UTF-8', 'UTF-8', 'a'),'','','C');
            $this->Ln();
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'Uživatelem:'));
            $this->Ln();

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

            foreach ($customer->addresses as $address)
            {
                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $address_types[$address['type']] . ':'),'','','L');
                $this->Ln();

                $this->SetFont('DejaVuSerif', '', '8');
                $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'firma:'),'','','R');
                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $address['company']));
                $this->Ln();

                $name = formatName($address, false);

                $street = "";
                if (isset($address["street"]))
                {
                        $street = $address["street"] . " " . $address["number"];
                }
                else
                {
                        $street = "č.p. " . $address["number"];;
                };

                $this->SetFont('DejaVuSerif', '', '8');

                if (is_null($address['company']))
                    $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'jméno:'),'','','R');
                else
                    $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'zastoupená:'),'','','R');

                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $name));
                $this->Ln();

                $this->SetFont('DejaVuSerif', '', '8');
                $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'ulice / č.p.:'),'','','R');
                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $street));
                $this->Ln();

                $this->SetFont('DejaVuSerif', '', '8');
                $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'PSČ / město:'),'','','R');
                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->Cell(60,4,iconv('UTF-8', 'UTF-8', substr($address["zip"],0,3) . ' ' . substr($address["zip"],3,2) . ' ' . $address['city']));
                $this->Ln();
                $this->Ln();
            }

            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'Ostatní údaje:'),'','','L');
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'datum narození:'),'','','R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['date_of_birth']));
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'číslo OP:'),'','','R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['identity_card_number']));
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'IČ:'),'','','R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['ic']));
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'DIČ:'),'','','R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['dic']));
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'tel:'),'','','R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $data['phone']));
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'e-mail:'),'','','R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->MultiCell(70,4,iconv('UTF-8', 'UTF-8', $data['email']));

            $this->Ln();

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Write(4, iconv('UTF-8', 'UTF-8', '
Já, níže podepsaný:

1. Uděluji tímto souhlas se shromažďováním, uchováním a zpracováním poskytnutých osobních údajů správcem NETAIR, s.r.o., (dále jen "správce"), pro účel stanovený níže. Tento souhlas uděluji pro následující údaje:
Jméno, příjmení, emailová adresa, telefonní číslo, adresa trvalého pobytu, adresa místa připojení, fakturační adresa, korespondenční adresa, datum narození, IP adresa

2. Účelem zpracování těchto osobních údajů je identifikace Uživatele, vedení evidence, daňové doklady ze strany správce.

3. Tento souhlas uděluji na dobu neurčitou a můžu ho kdykoli vzít zpět, a to stejným způsobem, jakým jsem jej udělil.

4. Zpracování osobních údajů je prováděno správcem.

5. Beru na vědomí, že podle zákona o ochraně osobních údajů mám právo:
a) vzít souhlas kdykoliv zpět,
b) požadovat po nás informaci, jaké vaše osobní údaje zpracováváme,
c) požadovat po nás vysvětlení ohledně zpracování osobních údajů,
d) vyžádat si u nás přístup k těmto údajům a tyto nechat aktualizovat nebo opravit,
e) požadovat po nás výmaz těchto osobních údajů,
f) v případě pochybností o dodržování povinností souvisejících se zpracováním osobních údajů obrátit se na nás nebo na Úřad pro ochranu osobních údajů.

Prohlášení správce
    Správce prohlašuje, že bude shromažďovat osobní údaje v rozsahu nezbytném pro naplnění stanoveného účelu a zpracovávat je pouze v souladu s účelem, k němuž byly shromážděny.  Zaměstnanci správce nebo jiné fyzické osoby, které zpracovávají osobní údaje na základě  smlouvy  se správcem a další osoby jsou povinni zachovávat mlčenlivost o osobních údajích, a to i po skončení pracovního poměru nebo prací.

            ano         -  souhlasím se zpracováním osobních údajů
                              (bez tohoto souhlasu není možné uzavřít smlouvu ani poskytovat službu)

            ano / ne  -  souhlasím se zasíláním veškeré korespondence spojené s měsíčním vyúčtováním *
            ano / ne  -  souhlasím se zasíláním informací o odstávkách a poruchách *
            ano / ne  -  souhlasím se zasíláním obchodních sdělení *

    * nehodící se škrtněte
'));

            //$pdf->SetFont('ZapfDingbats','', 10);
            //$pdf->Cell(10, 10, 4, 1, 0);

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Ln();
            $this->Ln();
            if ($signed) 
                $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ' . $data['now']),'','','C');
            else
                $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ____________________'),'','','C');

            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ____________________'),'','','C');

            $this->Ln(20);

            $this->Cell(90,4,'......................................................','','','C');
            $this->Cell(90,4,'......................................................','','','C');
            $this->Ln();
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Správce'),'','','C');
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Uživatel'),'','','C');
            $this->Ln();

            if ($signed) $this->Image('../images/signature.png',38, $this->GetY()-19, 35);

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
    $pdf->GenerateGDPRAgreement($customer, $type, $signed);
    $pdf->Output($contract->number . '_' . $type . '_' . \Cake\I18n\FrozenDate::i18nFormat('yyyy-MM-dd') . '.pdf', 'I');
    break;
default:
    exit;
}
?>