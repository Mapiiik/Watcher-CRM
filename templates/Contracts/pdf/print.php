<?php
//set image path for TCPDF
define('K_PATH_IMAGES', WWW_ROOT . 'legacy' . DS . 'images' . DS);

// define date format
\Cake\I18n\FrozenDate::setToStringFormat('dd.MM.yyyy');

use Cake\I18n\Number;

class ContractPDF extends TCPDF
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

    function GenerateHandoverProtocol($contract, $type = 'handover-protocol-installation', $signed = false, $technical_details = null)
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        $this->SetFont('DejaVuSerif', 'BI', '8');

        switch ($type) {
        case 'handover-protocol-installation':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187, 6, 'PŘEDÁVACÍ PROTOKOL', '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187, 2, 'ke Smlouvě o poskytování služeb', '', '', 'C');
            $this->Ln(3);
            break;
        case 'handover-protocol-uninstallation':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187, 6, 'PŘEDÁVACÍ PROTOKOL', '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187, 2, 'k ukončení Smlouvy o poskytování služeb', '', '', 'C');
            $this->Ln(3);
            break;
        }

        $this->Ln(4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        switch ($type) {
        case 'handover-protocol-installation':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(90, 4, 'číslo smlouvy:', '', '', 'C');
            $this->Cell(90, 4, 'datum zahájení poskytování služeb:', '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(90, 4, $contract->number, '', '', 'C');
            $this->Cell(90, 4, $contract->valid_from, '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX()+4, $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187, 2, 'mezi', '', '', 'C');
            $this->Ln();
            break;
        case 'handover-protocol-uninstallation':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(90, 4, 'číslo smlouvy:', '', '', 'C');
            $this->Cell(90, 4, 'datum ukončení poskytování služeb:', '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(90, 4, $contract->number, '', '', 'C');
            $this->Cell(90, 4, $contract->valid_until, '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187, 2, 'mezi', '', '', 'C');
            $this->Ln();
            break;
        };

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(45, 4, 'Poskytovatelem:');
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
        if (is_null($contract->customer->ic))
        {
            $this->Cell(60, 4, 'fyzická osoba nepodnikající');
        }
        else if (is_null($contract->billing_address->company))
        {
            $this->Cell(60, 4, 'fyzická osoba podnikající');
        }
        else
        {
            $this->Cell(60, 4, 'právnická osoba');
        }

        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        $addressStartY = $this->GetY();

        // BILLING
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60, 4, __('Billing Address') . ':', '', '', 'L');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'firma:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->company, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        if (is_null($contract->billing_address->company)) {
            $this->Cell(30, 4,  'jméno:', '', '', 'R');
        } else {
            $this->Cell(30, 4,  'zastoupená:', '', '', 'R');
        }
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->full_name, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'ulice / č.p.:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->street_and_number, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'PSČ / město:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->zip_and_city, '', 'L');

        // NEXT COLLUMN
        $addressStopY = $this->GetY();
        $this->SetY($addressStartY);
        $this->Ln();

        // SPECIFIERS
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'dat. nar.:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30, 4, $contract->customer->date_of_birth);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(10, 4, 'IČ:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        if (is_null($contract->customer->ic))
            $this->Cell(60, 4, 'X');
        else
            $this->Cell(60, 4, $contract->customer->ic);
        $this->Ln();

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'č. OP:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30, 4, $contract->customer->identity_card_number);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(10, 4, 'DIČ:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        if (is_null($contract->customer->dic))
            $this->Cell(60, 4, 'X');
        else
            $this->Cell(60, 4, $contract->customer->dic);

        $this->Ln();

        // CONTACT
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'tel:', '', '', 'L');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(70, 4, $contract->customer->phone, '', 'L');

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'e-mail:', '', '', 'L');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(70, 4, $contract->customer->email, '', 'L');

        // GO BACK TO END
        $this->SetY(max($this->GetY(), $addressStopY));

        // INSTALLATION ADDRESS
        if ($contract->has('installation_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Installation Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4);
            $this->MultiCell(180, 4, $contract->installation_address->full_address, '', 'L');                    
        }
        // DELIVERY ADDRESS
        if ($contract->has('delivery_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Delivery Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(180, 4, $contract->delivery_address->full_address, '', 'L');                    
        }
        // PERMANENT ADDRESS
        if ($contract->has('permanent_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Permanent Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(180, 4, $contract->permanent_address->full_address, '', 'L');                    
        }

        $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->Ln(4);

        // BEGIN INSTALLATION
        if ($type == 'handover-protocol-installation'):
        
        // ACCESS INFORMATION
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4 , 'Přístupové údaje a technické informace');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Write(4, 'Nastavení koncového bodu pro autorizaci přístupu do Poskytovatelovy sítě elektronických komunikací:');
        $this->Ln(5);                                    

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(4, 5);
        $this->Cell(45, 5,  'Přístupový bod / SSID', 1, 0, 'C');
        $this->Cell(45, 5,  'Přidělená IP adresa', 1, 0, 'C');
        $this->Cell(45, 5,  'Uživatelské jméno', 1, 0, 'C');
        $this->Cell(45, 5,  'Heslo', 1, 0, 'C');
        $this->Ln();                                    

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(4, 5);
        $this->Cell(45, 5,  isset($technical_details->ssid) ? $technical_details->ssid : '', 1, 0, 'C');
        $this->Cell(45, 5,  implode(', ', array_column($contract->ips, 'ip')), 1, 0, 'C');
        $this->Cell(45, 5,  isset($technical_details->radius_username) ? $technical_details->radius_username : '', 1, 0, 'C');
        $this->Cell(45, 5,  isset($technical_details->radius_password) ? $technical_details->radius_password : '', 1, 0, 'C');
        $this->Ln();                                    

        $this->Ln(1);                                    

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Write(4, 'Nastavení zařízení ve vnitřní síti Uživatele:');
        $this->Ln(5);                                    

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(4, 5);
        $this->Cell(36, 5,  'IPv4 rozsah', 1, 0, 'C');
        $this->Cell(36, 5,  'IPv4 brána', 1, 0, 'C');
        $this->Cell(36, 5,  'DNS servery', 1, 0, 'C');
        $this->Cell(36, 5,  'WiFi - SSID', 1, 0, 'C');
        $this->Cell(36, 5,  'WiFi - Heslo', 1, 0, 'C');
        $this->Ln();                                    

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(4, 5);
        $this->Cell(36, 5,  '192.168.1.0/24', 1, 0, 'C');
        $this->Cell(36, 5,  '192.168.1.1', 1, 0, 'C');
        $this->Cell(36, 5,  '79.98.156.2, 79.98.152.2', 1, 0, 'C');
        $this->Cell(36, 5,  '', 1, 0, 'C');
        $this->Cell(36, 5,  '', 1, 0, 'C');
        $this->Ln();                                    

        $this->Ln(1);

        $this->writeHTML(
            '<strong>Přístup do Uživatelského portálu je možné si aktivovat na Webu Poskytovatele:</strong><br />' . PHP_EOL
            . '<u>https://netair.cz/internet/uzivatelsky-portal</u>' . PHP_EOL,
            true, 0, false, true, ''
        );

        $this->Ln(4);                                    

        // BORROWED EQUIPMENTS
        if (count($contract->borrowed_equipments) > 0)
        {
            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Write(4, 'Poskytnutá zařízení');
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Write(4, 'Poskytovatel poskytne Uživateli pro dobu trvání Smlouvy bezúplatně tato zařízení:');

            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(4, 5);
            $this->Cell(130, 5,  'Zařízení', 1);
            $this->Cell(25, 5,  'Sériové číslo', 1, 0, 'C');
            $this->Cell(25, 5,  'Hodnota', 1, 0, 'R');
            $this->Ln();                                    

            $this->SetFont('DejaVuSerif', '', '8');
            foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                $this->Cell(4, 5);
                $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                $this->Cell(25, 5, $borrowed_equipment->serial_number, 1, 0, 'C');
                $this->Cell(25, 5, Number::currency($borrowed_equipment->equipment_type->price), 1, 0, 'R');
                $this->Ln();                                    
            }

            $this->Ln(2);

            $this->SetFont('DejaVuSerif', '', '8');
            $this->MultiCell(180, 4, 'Uživatel je povinen tato zařízení Poskytovateli vrátit bez zbytečných odkladů nejpozději po zániku Smlouvy.' . PHP_EOL, 0, 'J');
            $this->Ln(2);

            $this->SetFont('DejaVuSerif', 'B', '8');
        }

        // SOLD EQUIPMENTS
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Aktivační poplatek, prodaná zařízení a příslušenství a práce nad rámec aktivačního poplatku');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);


        $this->SetFont('DejaVuSerif', '', '8');
        if (count($contract->borrowed_equipments) > 0) {
            $this->MultiCell(180, 4, 'Aktivační poplatek zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci poskytnutých zařízení:' . PHP_EOL, 0, 'J');
        }
        else
        {
            $this->MultiCell(180, 4, 'Aktivační poplatek zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací:' . PHP_EOL, 0, 'J');
        }
        $this->Ln(1);

        $subtotal = 0;
        
        $this->Cell(4, 5);
        $this->Cell(155, 5, 'Aktivační poplatek', 1);
        $this->Cell(25, 5, Number::currency($subtotal = ($contract->minimum_duration <= 0) ? $contract->activation_fee_sum : $contract->activation_fee_with_obligation_sum), 1, 0, 'R');
        $this->Ln();

        $this->Ln(2);

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Write(4, 'Poskytovatel dodal Uživateli tato zařízení a příslušenství a provedl práce nad rámec aktivačního poplatku:');

        $this->Ln(5);

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(4, 5);
        $this->Cell(130, 5,  'Zařízení / příslušenství / práce', 1);
        $this->Cell(25, 5,  'Sériové číslo', 1, 0, 'C');
        $this->Cell(25, 5,  'Cena', 1, 0, 'R');
        $this->Ln();                                    

        $this->SetFont('DejaVuSerif', '', '8');
        foreach ($contract->sold_equipments as $sold_equipment) {
            $subtotal += $sold_equipment->equipment_type->price;
            $this->Cell(4, 5);
            $this->Cell(130, 5, $sold_equipment->equipment_type->name, 1);
            $this->Cell(25, 5, $sold_equipment->serial_number, 1, 0, 'C');
            $this->Cell(25, 5, Number::currency($sold_equipment->equipment_type->price), 1, 0, 'R');
            $this->Ln();                                    
        }
        for ($i = 1; $i <= 6; $i++) {
            $this->Cell(4, 5);
            $this->Cell(130, 5, '', 1);
            $this->Cell(25, 5, '', 1, 0, 'C');
            $this->Cell(25, 5, '', 1, 0, 'C');
            $this->Ln();                                    
        }

        $this->Ln(2);

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek a cenu prodaných zařízení, příslušenství a prací.' . PHP_EOL, 0, 'J');

        $this->Cell(4, 5);
        $this->Cell(155, 5, 'Celkem k úhradě', 1);
        $this->Cell(25, 5, Number::currency($subtotal), 1, 0, 'R');
        $this->Ln();

        $this->Ln(2);
        
        // CROSS
        $this->Ln(5);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY()); // --
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, 285); // \
        $this->Line($this->GetX(), 285, $this->GetX() + 187, $this->GetY()); // /
        $this->Line($this->GetX(), $this->GetY(), $this->GetX(), 285); // |
        $this->Line($this->GetX() + 187, $this->GetY(), $this->GetX() + 187, 285); // |
        $this->Line($this->GetX(), 285, $this->GetX() + 187, 285); // --

        // add a page
        $this->AddPage();

        // CASH PAYMENT
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Úhrada v hotovosti');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Ln(4);
        $this->MultiCell(180, 4, 'Placeno hotově: ____________________,- Kč, podpis příjemce: ____________________' . PHP_EOL, 0, 'J');
        $this->Ln(3);

        // CONNECTION POINT STATE
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Stav přípojného bodu po instalaci');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Ln(3);
        $this->MultiCell(180, 4, 'Síla signálu na straně Uživatele v případě bezdrátového připojení do sítě Poskytovatele (Tx / Rx): ____________________ dBm' . PHP_EOL, 0, 'J');
        $this->Ln(3);
        $this->MultiCell(180, 4, 'Pro případný servis je zapotřebí žebřík v minimální délce: ______ m' . PHP_EOL, 0, 'J');
        $this->Ln(3);
        
        // FINAL STATEMENTS
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Závěrečná ustanovení');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->MultiCell(180, 4, 'Uživatel svým podpisem stvrzuje, že výše uvedená zařízení převzal nainstalovaná a plně funkční, a zároveň se zavazuje uhradit částku aktivačního poplatku i cenu dodaných zařízení a příslušenství a prací nad rámec aktivačního poplatku nejpozději do 10 dnů ode dne doručení faktury (pokud nedošlo k úhradě v hotovosti potvrzené výše).' . PHP_EOL, 0, 'J');
        $this->Ln(3);
        $this->MultiCell(180, 4, 'Uživatel dále potvrzuje, že  souhlasí s provedenou instalací a nemá vůči ní žádné námitky a zároveň prohlašuje, že objednané služby jsou plně funkční.' . PHP_EOL, 0, 'J');
        $this->Ln(3);

        endif;
        // END INSTALLATION

        // BEGIN UNINSTALLATION
        if ($type == 'handover-protocol-uninstallation'):

        // BORROWED EQUIPMENTS
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Poskytnutá zařízení, jejich stav a náhrada nákladů');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        if (count($contract->borrowed_equipments) > 0)
        {
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Write(4, 'Poskytovatel poskytl Uživateli pro dobu trvání Smlouvy bezúplatně tato zařízení:');

            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(4, 5);
            $this->Cell(130, 5,  'Zařízení', 1);
            $this->Cell(25, 5,  'Sériové číslo', 1, 0, 'C');
            $this->Cell(25, 5,  'Hodnota', 1, 0, 'R');
            $this->Ln();                                    

            $this->SetFont('DejaVuSerif', '', '8');
            foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                $this->Cell(4, 5);
                $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                $this->Cell(25, 5, $borrowed_equipment->serial_number, 1, 0, 'C');
                $this->Cell(25, 5, Number::currency($borrowed_equipment->equipment_type->price), 1, 0, 'R');
                $this->Ln();                                    
            }

            $this->Ln(2);

            $this->SetFont('DejaVuSerif', 'U', '8');
            $this->Write(4, 'Stav zařízení v době deinstalace:');
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(22, 0, '▢ ano / ▢ ne - ');
            $this->MultiCell(170, 0, 'Poskytovateli byla umožněna zkouška funkčnosti zařízení na místě, jejich zapnutím, připojením se do jejich konfiguračního rozhraní pokud to umožňují a provedením diagnostiky' . PHP_EOL, 0, 'L');
            $this->Ln(1);

            $this->Cell(22, 0, '▢ ano / ▢ ne - ');
            $this->MultiCell(170, 0, 'provedením zkoušky funkčnosti zařízení na místě, byla zjištěna jeho nefunkčnost' . PHP_EOL, 0, 'L');
            $this->Ln(1);
            
            $this->Cell(22, 0, '▢ ano / ▢ ne - ');
            $this->MultiCell(170, 4, 'zařízení má viditelnou vadu nebo poškození způsobené neodborným zacházením ze strany Uživatele' . PHP_EOL, 0, 'L');
            $this->Ln(3);

            $this->Cell(30, 0, 'zjištěné nedostatky:', 0, 'L');
            $this->MultiCell(150, 0, '_______________________________________________________________________________________________________', 0, 'L');
            $this->Ln(2);
            $this->Cell(30, 0);
            $this->MultiCell(150, 0, '_______________________________________________________________________________________________________', 0, 'L');
            $this->Ln(2);
            $this->Cell(30, 0);
            $this->MultiCell(150, 0, '_______________________________________________________________________________________________________', 0, 'L');
            $this->Ln(2);
            
            $this->MultiCell(180, 4, 'V případě že provedení zkoušky funkčnosti těchto zařízení na místě nebylo umožněno, bude provedeno následně v provozovně Poskytovatele.' . PHP_EOL, 0, 'J');
            $this->Ln(1);

            $this->MultiCell(180, 4, 'V případě viditelné vady, poškození nebo zjištění nefunkčnosti těchto zařízení se Uživatel zavazuje uhradit hodnotu těchto zařízení.' . PHP_EOL, 0, 'J');
            $this->Ln(1);
        }
        $this->Ln(3);
        
        // CASH PAYMENT
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Write(4, 'Úhrada v hotovosti');
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Write(4, ' - placeno hotově: ____________________,- Kč, podpis příjemce: ____________________');
        $this->Ln();
        $this->Ln(4);

        // FINAL STATEMENTS
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Závěrečná ustanovení');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->MultiCell(180, 4, 'Poskytovatel svým podpisem stvrzuje, že uvedená zařízení převzal ve stavu popsaném výše.' . PHP_EOL, 0, 'J');
        $this->Ln(1);
        $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit hodnotu zařízení v případě jejich viditelné vady, poškození nebo zjištěné nefunkčnosti nejpozději do 10 dnů ode dne doručení faktury (pokud nedošlo k úhradě v hotovosti potvrzené výše).' . PHP_EOL, 0, 'J');
        $this->Ln(1);
        $this->MultiCell(180, 4, 'Uživatel dále potvrzuje, že souhlasí s provedenou deinstalací a nemá vůči ní žádné námitky.' . PHP_EOL, 0, 'J');
        $this->Ln(1);

        endif;
        // END UNINSTALLATION
        
        // SIGNS
        $this->SetFont('DejaVuSerif', '', '8');
        if ($this->GetY() > 240) $this->AddPage();
        $this->Ln();
        $this->Ln();
        if ($signed) 
            $this->Cell(90, 4, 'Datum podpisu: ' . new \Cake\I18n\FrozenDate(), '', '', 'C');
        else
            $this->Cell(90, 4, 'Datum podpisu: ____________________', '', '', 'C');

        $this->Cell(90, 4, 'Datum podpisu: ____________________', '', '', 'C');

        $this->Ln(20);

        $this->Cell(90, 4, '......................................................', '', '', 'C');
        $this->Cell(90, 4, '......................................................', '', '', 'C');
        $this->Ln();
        $this->Cell(90, 4, 'Poskytovatel', '', '', 'C');
        $this->Cell(90, 4, 'Uživatel', '', '', 'C');
        $this->Ln();

        if ($signed) $this->Image(K_PATH_IMAGES . 'signature.png', 38, $this->GetY() - 19, 35);

        $this->Close();
    }
    function GenerateContract($contract, $type = 'contract-new', $signed = false)
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        $this->SetFont('DejaVuSerif', 'BI', '8');

        switch ($type) {
        case 'contract-new':
        case 'contract-new-x':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187, 6, 'SMLOUVA', '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187, 2, 'o poskytování služeb', '', '', 'C');
            $this->Ln(3);
            break;
        case 'contract-amendment':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187, 6, 'DODATEK', '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187, 2, 'ke Smlouvě o poskytování služeb', '', '', 'C');
            $this->Ln(3);
            break;
        case 'contract-termination':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187, 6, 'DOHODA', '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187, 2, 'o ukončení Smlouvy o poskytování služeb', '', '', 'C');
            $this->Ln(3);
            break;
        }

        $this->Ln(4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        switch ($type) {
        case 'contract-new':
        case 'contract-new-x':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(90, 4, 'číslo smlouvy:', '', '', 'C');
            $this->Cell(90, 4, 'datum zahájení poskytování služeb:', '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(90, 4, $contract->number, '', '', 'C');
            $this->Cell(90, 4, $contract->valid_from, '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX()+4, $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187, 2, 'uzavřená mezi', '', '', 'C');
            $this->Ln();
            break;
        case 'contract-amendment':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(45, 4, 'číslo smlouvy:', '', '', 'C');
            $this->Cell(45, 4, 'datum uzavření smlouvy:', '', '', 'C');
            $this->Cell(45, 4, 'číslo dodatku:', '', '', 'C');
            $this->Cell(45, 4, 'datum účinnosti dodatku:', '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(45, 4, $contract->number, '', '', 'C');
            $this->Cell(45, 4, $contract->conclusion_date, '', '', 'C');
            $this->Cell(45, 4, $contract->number_of_amendments + 1, '', '', 'C');
            $this->Cell(45, 4, $contract->valid_from, '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187, 2, 'uzavřený mezi', '', '', 'C');
            $this->Ln();
            break;
        case 'contract-termination':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(60, 4, 'číslo smlouvy:', '', '', 'C');
            $this->Cell(60, 4, 'datum uzavření smlouvy:', '', '', 'C');
            $this->Cell(60, 4, 'datum ukončení poskytování služeb:', '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60, 4, $contract->number_of_the_contract_to_be_terminated, '', '', 'C');
            $this->Cell(60, 4, $contract->conclusion_date, '', '', 'C');
            $this->Cell(60, 4, $contract->valid_until, '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187, 2, 'uzavřená mezi', '', '', 'C');
            $this->Ln();
            break;
        };

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(45, 4, 'Poskytovatelem:');
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
        if (is_null($contract->customer->ic))
        {
            $this->Cell(60, 4, 'fyzická osoba nepodnikající');
        }
        else if (is_null($contract->billing_address->company))
        {
            $this->Cell(60, 4, 'fyzická osoba podnikající');
        }
        else
        {
            $this->Cell(60, 4, 'právnická osoba');
        }

        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());

        $addressStartY = $this->GetY();

        // BILLING
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60, 4, __('Billing Address') . ':', '', '', 'L');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'firma:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->company, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        if (is_null($contract->billing_address->company)) {
            $this->Cell(30, 4,  'jméno:', '', '', 'R');
        } else {
            $this->Cell(30, 4,  'zastoupená:', '', '', 'R');
        }
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->full_name, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'ulice / č.p.:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->street_and_number, '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30, 4, 'PSČ / město:', '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60, 4, $contract->billing_address->zip_and_city, '', 'L');

        // NEXT COLLUMN
        $addressStopY = $this->GetY();
        $this->SetY($addressStartY);
        $this->Ln();

        // SPECIFIERS
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'dat. nar.:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30, 4, $contract->customer->date_of_birth);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(10, 4, 'IČ:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        if (is_null($contract->customer->ic))
            $this->Cell(60, 4, 'X');
        else
            $this->Cell(60, 4, $contract->customer->ic);
        $this->Ln();

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'č. OP:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30, 4, $contract->customer->identity_card_number);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(10, 4, 'DIČ:');
        $this->SetFont('DejaVuSerif', 'B', '8');
        if (is_null($contract->customer->dic))
            $this->Cell(60, 4, 'X');
        else
            $this->Cell(60, 4, $contract->customer->dic);

        $this->Ln();

        // CONTACT
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'tel:', '', '', 'L');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(70, 4, $contract->customer->phone, '', 'L');

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15, 4, 'e-mail:', '', '', 'L');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(70, 4, $contract->customer->email, '', 'L');

        // GO BACK TO END
        $this->SetY(max($this->GetY(), $addressStopY));

        // INSTALLATION ADDRESS
        if ($contract->has('installation_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Installation Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4);
            $this->MultiCell(180, 4, $contract->installation_address->full_address, '', 'L');                    
        }
        // DELIVERY ADDRESS
        if ($contract->has('delivery_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Delivery Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(180, 4, $contract->delivery_address->full_address, '', 'L');                    
        }
        // PERMANENT ADDRESS
        if ($contract->has('permanent_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Permanent Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(180, 4, $contract->permanent_address->full_address, '', 'L');                    
        }

        $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->Ln(3);

        if ($type === 'contract-termination')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, 'Smluvní strany ujednávají ukončení smlouvy o poskytování služeb č. ' . $contract->number_of_the_contract_to_be_terminated . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) ke dni ' . $contract->valid_until . '.');
            $this->Ln();
            $this->Ln();
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Write(4, 'Tato dohoda je vyhotovena ve dvou stejnopisech.');
            $this->Ln();
        };

        if ($type === 'contract-new' || $type === 'contract-new-x')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, 'Smlouva je uzavřena ' . $this->contractDuration($contract->minimum_duration) . '.');
            $this->Ln();
            $this->Write(4, 'Datum zahájení poskytování služeb: ' . $contract->valid_from);
            $this->Ln();
            $this->Ln();

            if ($type === 'contract-new-x')
            {
                $this->Write(4, 'Smluvní strany zároveň ujednávají, že předchozí smlouva o poskytování služeb č. ' . $contract->number_of_the_contract_to_be_terminated . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) zaniká ke dni ' . $contract->valid_from->subDay(1) . '.');
                $this->Ln();
                $this->Ln();
            }
        }
        if ($type === 'contract-amendment')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, 'Tento dodatek mění Seznam poskytovaných služeb a Platební údaje původní smlouvy ve znění případných předchozích dodatků s účinností od ' . $contract->valid_from . ' takto:');
            $this->Ln();
            $this->Ln();
        }

        if ($type === 'contract-new' || $type === 'contract-new-x' || $type === 'contract-amendment')
        {
            if ($type === 'contract-amendment')
                $format = 'I';
            else
                $format = '';
            
            // sum of all items
            $totalCost = 0;

            // billing of pricelist items
            if (count($contract->standard_billings) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, '9');
                $this->Cell(187, 3, 'Seznam poskytovaných služeb a údaje o jejich aktuálních cenách dle Ceníku včetně DPH');
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '8');
                $this->Cell(4, 4);
                $this->Cell(135, 4, 'služba:', '', '', 'L');
                $this->Cell(40, 4, 'cena / měsíc:', '', '', 'R');
                $this->Ln();

                foreach ($contract->standard_billings as $billing)
                {
                    $this->SetFont('DejaVuSerif', 'B' . $format, '8');
                    $this->Cell(4, 4);
                    $this->Cell(135, 4, $billing->name, '', '', 'L');
                    $this->Cell(40, 4, Number::currency($billing->sum), '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, Number::currency(-$billing->percentage_discount_sum), '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva v pevné výši z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, Number::currency(-$billing->fixed_discount_sum), '', '', 'R');
                        $this->Ln();
                    }
                    
                    $totalCost += $billing->total_price;
                }
                $this->Ln();
            }

            // billing of non-pricelist items
            if (count($contract->individual_billings) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, '9');
                $this->Cell(187, 3, 'Seznam poskytovaných služeb a údaje o jejich individuálních cenách včetně DPH');
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '8');
                $this->Cell(4, 4);
                $this->Cell(135, 4, 'služba:', '', '', 'L');
                $this->Cell(40, 4, 'cena / měsíc:', '', '', 'R');
                $this->Ln();

                foreach ($contract->individual_billings as $billing)
                {
                    $this->SetFont('DejaVuSerif', 'B' . $format, '8');
                    $this->Cell(4, 4);
                    $this->Cell(135, 4, $billing->name, '', '', 'L');
                    $this->Cell(40, 4, Number::currency($billing->sum), '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, Number::currency(-$billing->percentage_discount_sum), '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva v pevné výši z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, Number::currency(-$billing->fixed_discount_sum), '', '', 'R');
                        $this->Ln();
                    }

                    $totalCost += $billing->total_price;
                }

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, 'Smluvní strany ujednávají, že výše cen za Poskytovatelovy služby je touto smlouvou ujednána oproti Ceníku v individuální výši. Včetně všech svých složek má proto povahu Poskytovatelova obchodního tajemství dle § 504 zákona č. 89/2012 Sb., občanského zákoníku.', '', 'L');

                $this->Ln();
            }
            
            $this->SetFont('DejaVuSerif', 'B' . $format, '9');
            $this->Cell(187, 3, 'Platební údaje');
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '' . $format, '8');
            $this->Cell(45, 4, 'perioda platby:', '', '', 'C');
            $this->Cell(45, 4, 'způsob úhrady:', '', '', 'C');
            $this->Cell(45, 4, 'datum 1. úhrady:', '', '', 'C');
            $this->Cell(45, 4, 'měsíční platba za služby celkem:', '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, '8');
            $this->Cell(45, 4, 'měsíčně', '', '', 'C');
            $this->Cell(45, 4, 'převodem z účtu', '', '', 'C');
            $this->Cell(45, 4, 'do ' . $contract->valid_from->day(1)->addMonth(1)->addDay(9), '', '', 'C');

            // reverse charge
            if ($contract->customer->taxe_id == 5) {
                $this->Cell(45, 4, Number::currency($totalCost - round($totalCost - ($totalCost / (1 + env('VAT_RATE', 0))), 2)) . ' *', '', '', 'C');
                $this->Ln();

                $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, '*faktury budou vystaveny v režimu přenesené daňové povinnosti dle § 92a zákona o dani z přidané hodnoty, kdy výši daně je povinen doplnit a přiznat plátce, pro kterého je plnění uskutečněno' . PHP_EOL, 0, 'J');
            } else {
                $this->Cell(45, 4, Number::currency($totalCost), '', '', 'C');
                $this->Ln();
            }

            $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '' . $format, '8');
            $this->Cell(90, 4, 'peněžní ústav poskytovatele:', '', '', 'C');
            $this->Cell(45, 4, 'číslo účtu poskytovatele:', '', '', 'C');
            $this->Cell(45, 4, 'variabilní symbol:', '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, '8');
            $this->Cell(90, 4, 'Komerční banka, a.s.', '', '', 'C');
            $this->Cell(45, 4, '207385091/0100', '', '', 'C');
            $this->Cell(45, 4, $contract->customer->number . " *", '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '' . $format, '7');
            $this->Cell(4, 4);
            $this->Cell(180, 4, '*doporučujeme nastavit si trvalý příkaz dle předepsaných platebních údajů, údaje lze použít i pro jednotlivé platby', '', '', 'L');
            $this->Ln();

            unset($format);
        };

        if ($type === 'contract-amendment')
        {
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Ln();
            $this->Write(4, 'Ustanovení smlouvy (ve znění případných předchozích dodatků) nedotčená tímto dodatkem zůstávají beze změn.');
            $this->Ln();
            $this->Ln();
            $this->Write(4, 'Tento dodatek je vyhotoven ve dvou stejnopisech.');
            $this->Ln();
        }

        if ($type === 'contract-new' || $type === 'contract-new-x')
        {
            // cross
            $this->Ln(5);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY()); // --
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, 285); // \
            $this->Line($this->GetX(), 285, $this->GetX() + 187, $this->GetY()); // /
            $this->Line($this->GetX(), $this->GetY(), $this->GetX(), 285); // |
            $this->Line($this->GetX() + 187, $this->GetY(), $this->GetX() + 187, 285); // |
            $this->Line($this->GetX(), 285, $this->GetX() + 187, 285); // --

            // add a page
            $this->AddPage();

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Ln();
            $this->Ln();
            $this->Write(4, 'Poskytnutá zařízení, aktivační poplatek a náhrada nákladů spojených s telekomunikačními zařízeními poskytnutými Uživateli za zvýhodněných podmínek');
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(1);

            if (count($contract->borrowed_equipments) > 0)
            {
                $this->SetFont('DejaVuSerif', '', '8');
                if ($type === 'contract-new')
                    $this->Write(4, 'Poskytovatel poskytne Uživateli pro dobu trvání této smlouvy bezúplatně tato zařízení:');
                else
                    $this->Write(4, 'Na základě uvedené předchozí smlouvy ze dne ' . $contract->conclusion_date . ' poskytl Poskytovatel Uživateli bezúplatně tato zařízení:');

                $this->Ln(5);
                
                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->Cell(4, 5);
                $this->Cell(130, 5,  'Zařízení', 1);
                $this->Cell(30, 5,  'Hodnota', 1, 0, 'R');
                $this->Ln();                                    

                $this->SetFont('DejaVuSerif', '', '8');
                foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                    $this->Cell(4, 5);
                    $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                    $this->Cell(30, 5, Number::currency($borrowed_equipment->equipment_type->price), 1, 0, 'R');
                    $this->Ln();                                    
                }

                $this->Ln();

                if ($type === 'contract-new-x')
                {
                    $this->MultiCell(180, 4, 'Smluvní strany ujednávají, že Poskytovatel Uživateli touto smlouvou uvedená zařízení nadále poskytuje k bezúplatnému užívání až do zániku této nové smlouvy.' . PHP_EOL, 0, 'J');
                    $this->Ln(3);
                }

                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->MultiCell(180, 4, 'Uživatel je srozuměn se skutečností, že při bezúplatném poskytnutí zařízení Poskytovatel neumožňuje změnu tarifu na tarif, který má dle Ceníku nižší měsíční cenu než 295,- Kč.' . PHP_EOL, 0, 'J');
                $this->Ln(3);

                $this->SetFont('DejaVuSerif', '', '8');
                $this->MultiCell(180, 4, 'Uživatel je povinen tato zařízení Poskytovateli vrátit bez zbytečných odkladů nejpozději po zániku této Smlouvy.' . PHP_EOL, 0, 'J');
                $this->Ln(3);

                $this->MultiCell(180, 4, 'Náklady spojené s instalací dalších zařízení nebo další kabeláže se řídí aktuálně účinným Ceníkem Poskytovatele.' . PHP_EOL, 0, 'J');
                $this->Ln(3);

                if ($contract->activation_fee_sum > 0)
                {
                    $this->SetFont('DejaVuSerif', 'B', '8');

                    if ($contract->minimum_duration <= 0)
                    {
                        if ($type === 'contract-new') {
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . Number::currency($contract->activation_fee_sum) . ' zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                    }
                    else
                    {
                        if ($type === 'contract-new') {
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . Number::currency($contract->activation_fee_with_obligation_sum) . ' zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                        $this->MultiCell(180, 4, 'Poskytnutá zařízení jsou Uživateli poskytnuta Poskytovatelem za zvýhodněných podmínek (bezúplatně). V případě zániku této smlouvy před uplynutím '. $this->contractDurationBefore($contract->minimum_duration) . ' od jejího uzavření je proto Uživatel povinen nahradit Poskytovateli náklady spojené s výše uvedenými Poskytnutými zařízeními, a to v paušální částce ' . Number::currency($contract->activation_fee_sum - $contract->activation_fee_with_obligation_sum) . ' (' . Number::currency($contract->activation_fee_sum) . ' je aktivační poplatek při smlouvě bez úvazku).' . PHP_EOL, 0, 'J');
                        $this->Ln(3);
                    }
                }
            }
            else
            {
                $this->SetFont('DejaVuSerif', '', '8');
                $this->MultiCell(180, 4, 'Cena za případnou instalaci Uživatelových zařízení včetně případných souvisejících nákladů (např. kabeláž) se řídí aktuálním Ceníkem Poskytovatele.' . PHP_EOL, 0, 'J');
                $this->Ln(3);

                if ($contract->activation_fee_sum > 0) {
                    $this->SetFont('DejaVuSerif', 'B', '8');

                    if ($contract->minimum_duration <= 0) {
                        if ($type === 'contract-new') {
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . Number::currency($contract->activation_fee_sum) . ' zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                    } else {
                        if ($type === 'contract-new') {
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . Number::currency($contract->activation_fee_with_obligation_sum) . ' zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                        $this->MultiCell(180, 4, 'Aktivační poplatek je Uživateli poskytnut Poskytovatelem za zvýhodněných podmínek. V případě zániku této smlouvy před uplynutím ' . $this->contractDurationBefore($contract->minimum_duration) . ' od jejího uzavření je proto Uživatel povinen nahradit Poskytovateli náklady spojené se zřízením koncového bodu Poskytovatelovy sítě elektronických komunikací, a to v paušální částce ' . Number::currency($contract->activation_fee_sum - $contract->activation_fee_with_obligation_sum) . ' (' . Number::currency($contract->activation_fee_sum) . ' je aktivační poplatek při smlouvě bez úvazku).' . PHP_EOL, 0, 'J');
                        $this->Ln(3);
                    }
                }
            }

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Write(4 , 'Závěrečná ustanovení');
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->setListIndentWidth(4);
            $this->writeHTML(
                'Uživatel prohlašuje, že se podrobně seznámil s obsahem těchto aktuálně účinných dokumentů (dále jako „Dokumenty“):' . PHP_EOL
                . '<ol>' . PHP_EOL
                . '  <li><b><i>Všeobecné podmínky služeb elektronických komunikací</i></b> (dále jako „Podmínky“)<ul>' . PHP_EOL
                . '    <li>Uživatel si je vědom skutečnosti, že Podmínky jsou nedílnou součástí této Smlouvy a zavazuje se je dodržovat.</li>' . PHP_EOL
                . '    <li>Uživateli je též známo, že Poskytovatel je oprávněn Podmínky v souladu s příslušnými právními předpisy jednostranně měnit.</li>' . PHP_EOL
                . '    <li>Podmínky obsahují mimo jiné i podrobné informace vyžadované § 63 odst. 1 zákona č. 127/2005 Sb. o elektronických komunikacích,'
                . ' jako jsou informace o veškerých podmínkách omezujících přístup k poskytovaným službám a možnostem jejich využívání,'
                . ' o minimální nabízené a minimální zaručené úrovni kvality poskytovaných služeb, o omezeních týkajících se omezení užívání koncových zařízení nebo o možnostech ukončení smlouvy.</li>' . PHP_EOL
                . '  </ul></li>' . PHP_EOL
//                . '  <li><b><i>Přehled parametrů a rychlostí poskytovaných tarifů pro služby připojení k internetu v pevném místě</i></b>, který je nedílnou součástí této smlouvy</li>' . PHP_EOL
                . '  <li><b><i>Přehled parametrů a rychlostí poskytovaných tarifů pro služby připojení k internetu v pevném místě</i></b></li>' . PHP_EOL
                . '  <li><b><i>Oznámení o typech rozhraní pro připojení k veřejné komunikační síti</i></b></li>' . PHP_EOL
                . '  <li><b><i>Zásady zpracování osobních údajů</i></b></li>' . PHP_EOL
                . '  <li><b><i>Ceník</i></b></li>' . PHP_EOL
                . '</ol>' . PHP_EOL
                . 'Uživatel potvrzuje, že Dokumenty od Poskytovatele obdržel k prostudování a s jejich obsahem plně souhlasí.<br>' . PHP_EOL
                . 'Uživatel je srozuměn se skutečností, že aktuální znění těchto Dokumentů, kterými se tato Smlouva řídí, je vždy dostupné na:' . PHP_EOL
                . '<ul>' . PHP_EOL
                . '  <li>poskytovatelových webových stránkách: <u>https://netair.cz</u>' . PHP_EOL
                . '  <li>ke dni uzavření této smlouvy konkrétně v této sekci: <u>https://netair.cz/internet/vseobecne-informace</u>' . PHP_EOL
                . '</ul>' . PHP_EOL,
                true, 0, false, true, ''
            );
            $this->Ln(3);
            $this->MultiCell(180, 4, 'Tato smlouva (č. ' . $contract->number . ') je vyhotovena ve dvou stejnopisech.' . PHP_EOL, 0, 'J');
            $this->Ln(3);
        }

        $this->SetFont('DejaVuSerif', '', '8');
        if ($this->GetY() > 240) $this->AddPage();
        $this->Ln();
        $this->Ln();
        if ($signed) 
            $this->Cell(90, 4, 'Datum podpisu: ' . new \Cake\I18n\FrozenDate(), '', '', 'C');
        else
            $this->Cell(90, 4, 'Datum podpisu: ____________________', '', '', 'C');

        $this->Cell(90, 4, 'Datum podpisu: ____________________', '', '', 'C');

        $this->Ln(20);

        $this->Cell(90, 4, '......................................................', '', '', 'C');
        $this->Cell(90, 4, '......................................................', '', '', 'C');
        $this->Ln();
        $this->Cell(90, 4, 'Poskytovatel', '', '', 'C');
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
case 'contract-new':
case 'contract-new-x':
case 'contract-amendment':
    //Generate PDF
    $pdf = new ContractPDF('P', 'mm', 'A4');
    $pdf->GenerateContract($contract, $type, $signed);
    $pdf->Output($contract->number . '_' . $type . '_' . $contract->valid_from->i18nFormat('yyyy-MM-dd') . '.pdf', 'I');
    break;
case 'contract-termination':
    //Generate PDF
    $pdf = new ContractPDF('P', 'mm', 'A4');
    $pdf->GenerateContract($contract, $type, $signed);
    $pdf->Output($contract->number . '_' . $type . '_' . $contract->valid_until->i18nFormat('yyyy-MM-dd') . '.pdf', 'I');
    break;
case 'handover-protocol-installation':
    //Generate PDF
    $pdf = new ContractPDF('P', 'mm', 'A4');
    $pdf->GenerateHandoverProtocol($contract, $type, $signed, $technical_details);
    $pdf->Output($contract->number . '_' . $type . '_' . $contract->valid_from->i18nFormat('yyyy-MM-dd') . '.pdf', 'I');
    break;
case 'handover-protocol-uninstallation':
    //Generate PDF
    $pdf = new ContractPDF('P', 'mm', 'A4');
    $pdf->GenerateHandoverProtocol($contract, $type, $signed);
    $pdf->Output($contract->number . '_' . $type . '_' . $contract->valid_until->i18nFormat('yyyy-MM-dd') . '.pdf', 'I');
    break;
default:
    exit;
}
?>