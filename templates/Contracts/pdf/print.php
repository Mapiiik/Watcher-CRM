<?php
//set image path for TCPDF
define('K_PATH_IMAGES', WWW_ROOT . 'legacy' . DS . 'images' . DS);

// define date format
\Cake\I18n\FrozenDate::setToStringFormat('dd.MM.YYYY');

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

    function GenerateHandoverProtocol($contract, $type = 'handover-protocol-installation', $signed = false)
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
            $this->Cell(4, 4);
            $this->MultiCell(180, 4, $contract->delivery_address->full_address, '', 'L');                    
        }
        // PERMANENT ADDRESS
        if ($contract->has('permanent_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Permanent Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->Cell(4, 4);
            $this->MultiCell(180, 4, $contract->permanent_address->full_address, '', 'L');                    
        }

        $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->Ln(4);
/*
        if ($type === 'contract-termination')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, 'Smluvní strany ujednávají ukončení smlouvy o poskytování služeb č. ' . $contract->number . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) ke dni ' . $contract->valid_until . '.');
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
                $this->Write(4, 'Smluvní strany zároveň ujednávají, že předchozí smlouva o poskytování služeb č. ' . $contract->number . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) zaniká ke dni ' . $contract->valid_from->subDay(1) . '.');
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
                    $this->Cell(40, 4, $billing->sum . ',- Kč', '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->percentage_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva v pevné výši z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->fixed_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }
                    
                    $totalCost += $billing->total;
                }
                $this->Ln();
            }

            // billing of non-pricelist items
            if (count($contract->individual_billings) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, '9');
                $this->Cell(187, 3, 'Seznam poskytovaných služeb a údaje o jejich individuálních cenách');
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
                    $this->Cell(40, 4, $billing->sum . ',- Kč', '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->percentage_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva v pevné výši z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->fixed_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }

                    $totalCost += $billing->total;
                }

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, 'Smluvní strany ujednávají, že výše cen za Poskytovatelovy služby je touto smlouvou ujednána oproti Ceníku v individuální výši. Včetně všech svých složek má proto povahu Poskytovatelova obchodního tajemství dle § 504 zákona č. 89/2012 Sb., občanského zákoníku.', '', 'L');

                $this->Ln();
            }
            
            // reverse charge
            if ($contract->customer->taxe_id == 5) {
                $totalCost = round($totalCost - ($totalCost / (1 + VAT_RATE)), 2);
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
                $this->Cell(45, 4, formatPrice($totalCost - round($totalCost - ($totalCost / (1 + VAT_RATE)), 2)) . ' *', '', '', 'C');
                $this->Ln();

                $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, '*faktury budou vystaveny v režimu přenesené daňové povinnosti dle § 92a zákona o dani z přidané hodnoty, kdy výši daně je povinen doplnit a přiznat plátce, pro kterého je plnění uskutečněno' . PHP_EOL, 0, 'J');
            } else {
                $this->Cell(45, 4, $totalCost . ',- Kč', '', '', 'C');
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
*/
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
        $this->Cell(45, 5,  $contract->ssid, 1, 0, 'C');
        $this->Cell(45, 5,  implode(', ', array_column($contract->ips, 'ip')), 1, 0, 'C');
        $this->Cell(45, 5,  $contract->radius_username, 1, 0, 'C');
        $this->Cell(45, 5,  $contract->radius_password, 1, 0, 'C');
        $this->Ln();                                    

        $this->Ln(4);                                    

        // SOLD EQUIPMENTS
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Prodaná zařízení a příslušenství:');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Write(4, 'Poskytovatel dodal Uživateli tato zařízení a příslušenství:');

        $this->Ln(5);

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(4, 5);
        $this->Cell(120, 5,  'Zařízení / příslušenství', 1);
        $this->Cell(30, 5,  'Sériové číslo', 1, 0, 'C');
        $this->Cell(30, 5,  'Cena', 1, 0, 'R');
        $this->Ln();                                    

        $this->SetFont('DejaVuSerif', '', '8');
        foreach ($contract->sold_equipments as $sold_equipment) {
            $this->Cell(4, 5);
            $this->Cell(120, 5, $sold_equipment->equipment_type->name, 1);
            $this->Cell(30, 5, $sold_equipment->serial_number, 1, 0, 'C');
            $this->Cell(30, 5, $sold_equipment->equipment_type->price . ',- Kč', 1, 0, 'R');
            $this->Ln();                                    
        }
        for ($i = 1; $i <= 3; $i++) {
            $this->Cell(4, 5);
            $this->Cell(120, 5, '', 1);
            $this->Cell(30, 5, '', 1, 0, 'C');
            $this->Cell(30, 5, '', 1, 0, 'R');
            $this->Ln();                                    
        }

        $this->Ln(2);

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(180, 4, 'Uživatel je povinen cenu těchto zařízení Poskytovateli uhradit.' . PHP_EOL, 0, 'J');
        $this->Ln(4);

        // BORROWED EQUIPMENTS
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Poskytnutá zařízení, aktivační poplatek a náhrada nákladů');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        if (count($contract->borrowed_equipments) > 0)
        {
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Write(4, 'Poskytovatel poskytne Uživateli pro dobu trvání Smlouvy bezúplatně tato zařízení:');

            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(4, 5);
            $this->Cell(120, 5,  'Zařízení', 1);
            $this->Cell(30, 5,  'Sériové číslo', 1, 0, 'C');
            $this->Cell(30, 5,  'Hodnota', 1, 0, 'R');
            $this->Ln();                                    

            $this->SetFont('DejaVuSerif', '', '8');
            foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                $this->Cell(4, 5);
                $this->Cell(120, 5, $borrowed_equipment->equipment_type->name, 1);
                $this->Cell(30, 5, $borrowed_equipment->serial_number, 1, 0, 'C');
                $this->Cell(30, 5, $borrowed_equipment->equipment_type->price . ',- Kč', 1, 0, 'R');
                $this->Ln();                                    
            }

            $this->Ln(2);

            $this->SetFont('DejaVuSerif', '', '8');
            $this->MultiCell(180, 4, 'Uživatel je povinen tato zařízení Poskytovateli vrátit bez zbytečných odkladů nejpozději po zániku Smlouvy.' . PHP_EOL, 0, 'J');
            $this->Ln(3);

            $this->MultiCell(180, 4, 'Náklady spojené s instalací dalších zařízení nebo další kabeláže se řídí aktuálně účinným Ceníkem Poskytovatele.' . PHP_EOL, 0, 'J');
            $this->Ln(3);

            if ($contract->activation_fee_sum > 0)
            {
                $this->SetFont('DejaVuSerif', 'B', '8');

                if ($contract->minimum_duration <= 0)
                {
                    $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL, 0, 'J');
                    $this->Ln(3);
                }
                else
                {
                    $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_with_obligation_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL, 0, 'J');
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
                    $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL, 0, 'J');
                    $this->Ln(3);
                } else {
                    $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_with_obligation_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL, 0, 'J');
                    $this->Ln(3);
                }
            }
        }

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
        $this->MultiCell(180, 4, 'Placeno hotově ____________________,- Kč, podpis příjemce: ____________________' . PHP_EOL, 0, 'J');
        $this->Ln(4);
        
        // FINAL STATEMENTS
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Write(4, 'Závěrečná ustanovení');
        $this->Ln();

        $this->Ln(0.4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187, $this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->MultiCell(180, 4, 'Svým podpisem stvrzuji, že jsem výše uvedená zařízení převzal nainstalovaná a plně funkční, a zároveň se zavazuji uhradit částku aktivačního poplatku i cenu zakoupených zařízení nejpozději do 10 dnů ode dne doručení faktury (pokud nedošlo k úhradě v hotovosti potvrzené výše).' . PHP_EOL, 0, 'J');
        $this->Ln(3);
        $this->MultiCell(180, 4, 'Dále potvrzuji, že  souhlasím s provedenou instalací a nemám vůči ní žádné námitky. Zároveň prohlašuji, že objednané služby jsou plně funkční.' . PHP_EOL, 0, 'J');
        $this->Ln(3);

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
            $this->Cell(60, 4, $contract->number, '', '', 'C');
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
            $this->Cell(4, 4);
            $this->MultiCell(180, 4, $contract->delivery_address->full_address, '', 'L');                    
        }
        // PERMANENT ADDRESS
        if ($contract->has('permanent_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30, 4, __('Permanent Address') . ': ', '' , '' , 'L');
            $this->Ln();
            $this->Cell(4, 4);
            $this->MultiCell(180, 4, $contract->permanent_address->full_address, '', 'L');                    
        }

        $this->Line($this->GetX() + 4, $this->GetY(), $this->GetX() + 187, $this->GetY());

        $this->Ln(3);

        if ($type === 'contract-termination')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, 'Smluvní strany ujednávají ukončení smlouvy o poskytování služeb č. ' . $contract->number . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) ke dni ' . $contract->valid_until . '.');
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
                $this->Write(4, 'Smluvní strany zároveň ujednávají, že předchozí smlouva o poskytování služeb č. ' . $contract->number . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) zaniká ke dni ' . $contract->valid_from->subDay(1) . '.');
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
                    $this->Cell(40, 4, $billing->sum . ',- Kč', '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->percentage_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva v pevné výši z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->fixed_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }
                    
                    $totalCost += $billing->total;
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
                    $this->Cell(40, 4, $billing->sum . ',- Kč', '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->percentage_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4, 4);
                        $this->Cell(135, 4, ' - sleva v pevné výši z ceny této služby', '', '', 'L');
                        $this->Cell(40, 4, -$billing->fixed_discount_sum . ',- Kč', '', '', 'R');
                        $this->Ln();
                    }

                    $totalCost += $billing->total;
                }

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, 'Smluvní strany ujednávají, že výše cen za Poskytovatelovy služby je touto smlouvou ujednána oproti Ceníku v individuální výši. Včetně všech svých složek má proto povahu Poskytovatelova obchodního tajemství dle § 504 zákona č. 89/2012 Sb., občanského zákoníku.', '', 'L');

                $this->Ln();
            }
            
            // reverse charge
            if ($contract->customer->taxe_id == 5) {
                $totalCost = round($totalCost - ($totalCost / (1 + VAT_RATE)), 2);
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
                $this->Cell(45, 4, formatPrice($totalCost - round($totalCost - ($totalCost / (1 + VAT_RATE)), 2)) . ' *', '', '', 'C');
                $this->Ln();

                $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, '*faktury budou vystaveny v režimu přenesené daňové povinnosti dle § 92a zákona o dani z přidané hodnoty, kdy výši daně je povinen doplnit a přiznat plátce, pro kterého je plnění uskutečněno' . PHP_EOL, 0, 'J');
            } else {
                $this->Cell(45, 4, $totalCost . ',- Kč', '', '', 'C');
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
                    $this->Cell(30, 5, $borrowed_equipment->equipment_type->price . ',- Kč', 1, 0, 'R');
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
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                    }
                    else
                    {
                        if ($type === 'contract-new') {
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_with_obligation_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                        $this->MultiCell(180, 4, 'Poskytnutá zařízení jsou Uživateli poskytnuta Poskytovatelem za zvýhodněných podmínek (bezúplatně). V případě zániku této smlouvy před uplynutím '. $this->contractDurationBefore($contract->minimum_duration) . ' od jejího uzavření je proto Uživatel povinen nahradit Poskytovateli náklady spojené s výše uvedenými Poskytnutými zařízeními, a to v paušální částce ' . ($contract->activation_fee_sum - $contract->activation_fee_with_obligation_sum) . ',- Kč (' . $contract->activation_fee_sum . ',- Kč je aktivační poplatek při smlouvě bez úvazku).' . PHP_EOL, 0, 'J');
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
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                    } else {
                        if ($type === 'contract-new') {
                            $this->MultiCell(180, 4, 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->activation_fee_with_obligation_sum . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL, 0, 'J');
                            $this->Ln(3);
                        }
                        $this->MultiCell(180, 4, 'Aktivační poplatek je Uživateli poskytnut Poskytovatelem za zvýhodněných podmínek. V případě zániku této smlouvy před uplynutím ' . $this->contractDurationBefore($contract->minimum_duration) . ' od jejího uzavření je proto Uživatel povinen nahradit Poskytovateli náklady spojené se zřízením koncového bodu Poskytovatelovy sítě elektronických komunikací, a to v paušální částce ' . ($contract->activation_fee_sum - $contract->activation_fee_with_obligation_sum) . ',- Kč (' . $contract->activation_fee_sum . ',- Kč je aktivační poplatek při smlouvě bez úvazku).' . PHP_EOL, 0, 'J');
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
            $this->MultiCell(180, 4,
                'Uživatel prohlašuje, že se podrobně seznámil s obsahem aktuálně účinných Všeobecných podmínek služeb elektronických komunikací (dále jako "Podmínky") a potvrzuje, že je od Poskytovatele obdržel a s jejich obsahem plně souhlasí.'
//                                'Uživatel prohlašuje, že se podrobně seznámil s obsahem aktuálně účinných Všeobecných podmínek služeb elektronických komunikací ze dne 22.2.2021 i s obsahem Všeobecných podmínek služeb elektronických komunikací ze dne 23.03.2021 účinných od 01.05.2021 (dále společně jako "Podmínky") a potvrzuje, že je od Poskytovatele obdržel a s jejich obsahem plně souhlasí.'
                . ' Je si vědom skutečnosti, že Podmínky jsou nedílnou součástí této smlouvy jako příloha č. 3 a zavazuje se je dodržovat.'
//                                . ' Je si vědom skutečnosti, že Podmínky jsou nedílnou součástí této smlouvy jako příloha č. 3 a č. 4 a zavazuje se je dodržovat.'
                . ' Je mu též známo, že Poskytovatel je oprávněn Podmínky v souladu s příslušnými právními předpisy jednostranně měnit.'
                . ' Podmínky obsahují mimo jiné i podrobné informace vyžadované § 63 odst. 1 zákona č. 127/2005 Sb. o elektronických komunikacích,'
                . ' jako jsou informace o veškerých podmínkách omezujících přístup k poskytovaným službám a možnostem jejich využívání,'
                . ' o minimální nabízené a minimální zaručené úrovni kvality poskytovaných služeb, o omezeních týkajících se omezení užívání koncových zařízení nebo o možnostech ukončení smlouvy.' . PHP_EOL, 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, 'Uživatel uděluje Poskytovateli souhlas se zpracováním svých osobních údajů. Informace pro subjekt osobních údajů je přílohou č. 1 této smlouvy.' . PHP_EOL, 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, 'Uživatel prohlašuje, že se podrobně seznámil s aktuálně účinným Ceníkem Poskytovatele, který je volně dostupný na Webu.' . PHP_EOL, 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, 'Uživatel prohlašuje, že se podrobně seznámil s aktuálně účinným „Přehledem parametrů a rychlostí poskytovaných tarifů pro služby připojení k internetu v pevném místě“, který je nedílnou součástí této smlouvy jako příloha č. 2.' . PHP_EOL, 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, 'Tato smlouva (č. ' . $contract->number . ') je vyhotovena ve dvou stejnopisech.' . PHP_EOL, 0, 'J');
            $this->Ln(3);

            $this->Write(4, 'Přílohy:');
            $this->Ln();
            $this->Write(4, '   1) Souhlas se zpracováním osobních údajů');
            $this->Ln();
            $this->Write(4, '   2) Přehled parametrů a rychlostí poskytovaných tarifů pro služby přístupu k internetu');
            $this->Ln();
            $this->Write(4, '   3) Všeobecné podmínky služeb elektronických komunikací v aktuálním znění');
            $this->Ln();
//            $this->Write(4, '   4) Všeobecné podmínky služeb elektronických komunikací platné od 01.05.2021');
//            $this->Ln();
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
    $pdf->GenerateHandoverProtocol($contract, $type, $signed);
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