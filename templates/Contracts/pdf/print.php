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

    function GenerateContract($contract, $type = 'contract-new', $signed = false)
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        $this->SetFont('DejaVuSerif', 'BI', '8');
//        $this->Ln(9);
/*
        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
        $this->Ln(0.4);
        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
        $this->Ln(2);
*/
        switch ($type) {
        case 'contract-new':
        case 'contract-new-x':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187,6,iconv('UTF-8', 'UTF-8', 'SMLOUVA'), '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'o poskytování služeb'), '', '', 'C');
            $this->Ln(3);
            break;
        case 'contract-amendment':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187,6,iconv('UTF-8', 'UTF-8', 'DODATEK'), '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'ke Smlouvě o poskytování služeb'), '', '', 'C');
            $this->Ln(3);
            break;
        case 'contract-termination':
            $this->SetFont('DejaVuSerif', 'B', '18');
            $this->Cell(187,6,iconv('UTF-8', 'UTF-8', 'DOHODA'), '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B', '12');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'o ukončení Smlouvy o poskytování služeb'), '', '', 'C');
            $this->Ln(3);
            break;
        }

        $this->Ln(4);
        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

        switch ($type) {
        case 'contract-new':
        case 'contract-new-x':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'číslo smlouvy:'), '', '', 'C');
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'datum zahájení poskytování služeb:'), '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', $contract->number), '', '', 'C');
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', $contract->valid_from), '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'uzavřená mezi'), '', '', 'C');
            $this->Ln();
            break;
        case 'contract-amendment':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'číslo smlouvy:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'datum uzavření smlouvy:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'číslo dodatku:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'účinnost dodatku:'), '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', $contract->number), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', $contract->conclusion_date), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', $contract->number_of_amendments + 1), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', $contract->valid_from), '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'uzavřený mezi'), '', '', 'C');
            $this->Ln();
            break;
        case 'contract-termination':
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'číslo smlouvy:'), '', '', 'C');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'datum uzavření smlouvy:'), '', '', 'C');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'datum ukončení poskytování služeb:'), '', '', 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $contract->number), '', '', 'C');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $contract->conclusion_date), '', '', 'C');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $contract->valid_until), '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(3);

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'uzavřená mezi'), '', '', 'C');
            $this->Ln();
            break;
        };

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'Poskytovatelem:'));
        $this->Ln();

        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

        $this->Ln(1);
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4, 'NETAIR, s.r.o.');
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4, '');
        $this->Cell(15,4, 'tel:', '', '', 'L');
        $this->Cell(40,4, '+420 488 572 050');
        $this->Ln();

        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4,iconv('UTF-8', 'UTF-8', 'Jablonec nad Jizerou 299'));
        $this->Cell(20,4, '', '', '', 'R');
        $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'IČ:'));
        $this->Cell(40,4, '27496139');
        $this->Cell(15,4, 'mobil:', '', '', 'L');
        $this->Cell(40,4, '+420 604 553 444');
        $this->Ln();

        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4, '512 43 Jablonec nad Jizerou');
        $this->Cell(20,4, '', '', '', 'R');
        $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'DIČ:'));
        $this->Cell(40,4, 'CZ27496139');
        $this->Cell(15,4, 'e-mail:', '', '', 'L');
        $this->Cell(40,4, 'mail@netair.cz');
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4, '');
        $this->MultiCell(157,4,iconv('UTF-8', 'UTF-8', 'zastoupeným Marko Jujnovićem, jednatelem'), '', 'L');
        $this->Cell(30,4, '');
        $this->MultiCell(157,4,iconv('UTF-8', 'UTF-8', 'zapsaným v obchodním rejstříku vedeném u Krajského soudu v Hradci Králové, oddíl C, vložka 22450.'), '', 'L');

        $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
        $this->Ln(3);

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(187,4,iconv('UTF-8', 'UTF-8', 'a'), '', '', 'C');
        $this->Ln();
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'Uživatelem:'));

        $this->SetFont('DejaVuSerif', '', '8');
        if (is_null($contract->customer->ic))
        {
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'fyzická osoba nepodnikající'));
        }
        else if (is_null($contract->billing_address->company))
        {
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'fyzická osoba podnikající'));
        }
        else
        {
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'právnická osoba'));
        }

        $this->Ln();

        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

        $addressStartY = $this->GetY();

        // BILLING
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60,4,iconv('UTF-8', 'UTF-8', __('Billing Address') . ':'), '', '', 'L');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'firma:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60,4,iconv('UTF-8', 'UTF-8', $contract->billing_address->company), '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        if (is_null($contract->billing_address->company)) {
            $this->Cell(30, 4, iconv('UTF-8', 'UTF-8', 'jméno:'), '', '', 'R');
        } else {
            $this->Cell(30, 4, iconv('UTF-8', 'UTF-8', 'zastoupená:'), '', '', 'R');
        }
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60,4,iconv('UTF-8', 'UTF-8', $contract->billing_address->full_name), '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'ulice / č.p.:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60,4,iconv('UTF-8', 'UTF-8', $contract->billing_address->street . ' ' . $contract->billing_address->number), '', 'L');

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'PSČ / město:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(60,4,iconv('UTF-8', 'UTF-8', $contract->billing_address->zip . ' ' . $contract->billing_address->city), '', 'L');

        // NEXT COLLUMN
        $addressStopY = $this->GetY();
        $this->SetY($addressStartY);
        $this->Ln();

        // SPECIFIERS
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15,4,iconv('UTF-8', 'UTF-8', 'dat. nar.:'));
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', $contract->customer->date_of_birth));
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'IČ:'));
        $this->SetFont('DejaVuSerif', 'B', '8');
        if (is_null($contract->customer->ic))
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'X'));
        else
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $contract->customer->ic));
        $this->Ln();

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15,4,iconv('UTF-8', 'UTF-8', 'č. OP:'));
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', $contract->customer->identity_card_number));
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'DIČ:'));
        $this->SetFont('DejaVuSerif', 'B', '8');
        if (is_null($contract->customer->dic))
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'X'));
        else
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $contract->customer->dic));

        $this->Ln();

        // CONTACT
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15,4,iconv('UTF-8', 'UTF-8', 'tel:'), '', '', 'L');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(70,4,iconv('UTF-8', 'UTF-8', $contract->customer->phone), '', 'L');

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(15,4,iconv('UTF-8', 'UTF-8', 'e-mail:'), '', '', 'L');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->MultiCell(70,4,iconv('UTF-8', 'UTF-8', $contract->customer->email), '', 'L');

        // GO BACK TO END
        $this->SetY(max($this->GetY(), $addressStopY));

        // INSTALLATION ADDRESS
        if ($contract->has('installation_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', __('Installation Address') . ': '), '' , '' , 'L');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30,4);
            $this->MultiCell(180,4,iconv('UTF-8', 'UTF-8', $contract->installation_address->full_address), '', 'L');                    
        }
        // DELIVERY ADDRESS
        if ($contract->has('delivery_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', __('Delivery Address') . ': '), '' , '' , 'L');
            $this->Ln();
            $this->Cell(4,4);
            $this->MultiCell(180,4,iconv('UTF-8', 'UTF-8', $contract->delivery_address->full_address), '', 'L');                    
        }
        // PERMANENT ADDRESS
        if ($contract->has('permanent_address')) {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', __('Permanent Address') . ': '), '' , '' , 'L');
            $this->Ln();
            $this->Cell(4,4);
            $this->MultiCell(180,4,iconv('UTF-8', 'UTF-8', $contract->permanent_address->full_address), '', 'L');                    
        }

        $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());

        $this->Ln(3);

        if ($type === 'contract-termination')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Smluvní strany ujednávají ukončení smlouvy o poskytování služeb č. ' . $contract->number . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) ke dni ' . $contract->valid_until . '.'));
            $this->Ln();
            $this->Ln();
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Tato dohoda je vyhotovena ve dvou stejnopisech.'));
            $this->Ln();
        };

        if ($type === 'contract-new' || $type === 'contract-new-x')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Smlouva je uzavřena ' . $this->contractDuration($contract->minimum_duration) . '.'));
            $this->Ln();
            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Datum zahájení poskytování služeb: ' . $contract->valid_from));
            $this->Ln();
            $this->Ln();

            if ($type === 'contract-new-x')
            {
                $this->Write(4, iconv('UTF-8', 'UTF-8', 'Smluvní strany zároveň ujednávají, že předchozí smlouva o poskytování služeb č. ' . $contract->number . ' ze dne ' . $contract->conclusion_date . ' (ve znění případných pozdějších dodatků) zaniká ke dni ' . $contract->valid_from->subDay(1) . '.'));
                $this->Ln();
                $this->Ln();
            }
        }
        if ($type === 'contract-amendment')
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Tento dodatek mění Seznam poskytovaných služeb a Platební údaje původní smlouvy ve znění případných předchozích dodatků s účinností od ' . $contract->valid_from . ' takto:'));
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
                $this->Cell(187,3,iconv('UTF-8', 'UTF-8', 'Seznam poskytovaných služeb a údaje o jejich aktuálních cenách dle Ceníku včetně DPH'));
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '8');
                $this->Cell(4,4);
                $this->Cell(135,4,iconv('UTF-8', 'UTF-8', 'služba:'), '', '', 'L');
                $this->Cell(40,4,iconv('UTF-8', 'UTF-8', 'cena / měsíc:'), '', '', 'R');
                $this->Ln();

                foreach ($contract->standard_billings as $billing)
                {
                    $this->SetFont('DejaVuSerif', 'B' . $format, '8');
                    $this->Cell(4,4);
                    $this->Cell(135,4,iconv('UTF-8', 'UTF-8', $billing->name), '', '', 'L');
                    $this->Cell(40,4,iconv('UTF-8', 'UTF-8', $billing->sum . ',- Kč'), '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4,4);
                        $this->Cell(135,4,iconv('UTF-8', 'UTF-8', ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby'), '', '', 'L');
                        $this->Cell(40,4,iconv('UTF-8', 'UTF-8', -$billing->percentage_discount_sum . ',- Kč'), '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4,4);
                        $this->Cell(135,4,iconv('UTF-8', 'UTF-8', ' - sleva v pevné výši z ceny této služby'), '', '', 'L');
                        $this->Cell(40,4,iconv('UTF-8', 'UTF-8', -$billing->fixed_discount_sum . ',- Kč'), '', '', 'R');
                        $this->Ln();
                    }
                    
                    $totalCost += $billing->total;
                }
                $this->Ln();
            }

            // billing of non-pricelist items
            if (count($contract->individual_billings) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, '9');
                $this->Cell(187,3,iconv('UTF-8', 'UTF-8', 'Seznam poskytovaných služeb a údaje o jejich individuálních cenách'));
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '8');
                $this->Cell(4,4);
                $this->Cell(135,4,iconv('UTF-8', 'UTF-8', 'služba:'), '', '', 'L');
                $this->Cell(40,4,iconv('UTF-8', 'UTF-8', 'cena / měsíc:'), '', '', 'R');
                $this->Ln();

                foreach ($contract->individual_billings as $billing)
                {
                    $this->SetFont('DejaVuSerif', 'B' . $format, '8');
                    $this->Cell(4,4);
                    $this->Cell(135,4,iconv('UTF-8', 'UTF-8', $billing->name), '', '', 'L');
                    $this->Cell(40,4,iconv('UTF-8', 'UTF-8', $billing->sum . ',- Kč'), '', '', 'R');
                    $this->Ln();

                    if ($billing->percentage_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4,4);
                        $this->Cell(135,4,iconv('UTF-8', 'UTF-8', ' - sleva ve výši ' . $billing->percentage_discount . ' % z ceny této služby'), '', '', 'L');
                        $this->Cell(40,4,iconv('UTF-8', 'UTF-8', -$billing->percentage_discount_sum . ',- Kč'), '', '', 'R');
                        $this->Ln();
                    }
                    if ($billing->fixed_discount_sum > 0) {
                        $this->SetFont('DejaVuSerif', '' . $format, '8');
                        $this->Cell(4,4);
                        $this->Cell(135,4,iconv('UTF-8', 'UTF-8', ' - sleva v pevné výši z ceny této služby'), '', '', 'L');
                        $this->Cell(40,4,iconv('UTF-8', 'UTF-8', -$billing->fixed_discount_sum . ',- Kč'), '', '', 'R');
                        $this->Ln();
                    }

                    $totalCost += $billing->total;
                }

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4,4, iconv('UTF-8', 'UTF-8', ''), '', '', 'C');
                $this->MultiCell(180,4, iconv('UTF-8', 'UTF-8', 'Smluvní strany ujednávají, že výše cen za Poskytovatelovy služby je touto smlouvou ujednána oproti Ceníku v individuální výši. Včetně všech svých složek má proto povahu Poskytovatelova obchodního tajemství dle § 504 zákona č. 89/2012 Sb., občanského zákoníku.'), '', 'L');

                $this->Ln();
            }
            
            // reverse charge
            if ($contract->customer->taxe_id == 5) {
                $totalCost = round($totalCost - ($totalCost / (1 + VAT_RATE)), 2);
            }

            $this->SetFont('DejaVuSerif', 'B' . $format, '9');
            $this->Cell(187,3,iconv('UTF-8', 'UTF-8', 'Platební údaje'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '' . $format, '8');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'perioda platby:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'způsob úhrady:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'datum 1. úhrady:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'měsíční platba za služby celkem:'), '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, '8');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'měsíčně'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'převodem z účtu'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'do ' . $contract->valid_from->day(1)->addMonth(1)->addDay(9)), '', '', 'C');

            // reverse charge
            if ($contract->customer->taxe_id == 5) {
                $this->Cell(45,4,iconv('UTF-8', 'UTF-8', formatPrice($totalCost - round($totalCost - ($totalCost / (1 + VAT_RATE)), 2)) . ' *'), '', '', 'C');
                $this->Ln();

                $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '' . $format, '7');
                $this->Cell(4,4, iconv('UTF-8', 'UTF-8', ''), '', '', 'C');
                $this->MultiCell(180,4, iconv('UTF-8', 'UTF-8', '*faktury budou vystaveny v režimu přenesené daňové povinnosti dle § 92a zákona o dani z přidané hodnoty, kdy výši daně je povinen doplnit a přiznat plátce, pro kterého je plnění uskutečněno' . PHP_EOL), 0, 'J');
            } else {
                $this->Cell(45,4,iconv('UTF-8', 'UTF-8', $totalCost . ',- Kč'), '', '', 'C');
                $this->Ln();
            }

            $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '' . $format, '8');
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'peněžní ústav poskytovatele:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'číslo účtu poskytovatele:'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'variabilní symbol:'), '', '', 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, '8');
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Komerční banka, a.s.'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', '207385091/0100'), '', '', 'C');
            $this->Cell(45,4,iconv('UTF-8', 'UTF-8', $contract->customer->number . " *"), '', '', 'C');
            $this->Ln();

            $this->Line($this->GetX()+4,$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '' . $format, '7');
            $this->Cell(4,4, iconv('UTF-8', 'UTF-8', ''), '', '', 'C');
            $this->Cell(180,4, iconv('UTF-8', 'UTF-8', '*doporučujeme nastavit si trvalý příkaz dle předepsaných platebních údajů, údaje lze použít i pro jednotlivé platby'), '', '', 'L');
            $this->Ln();

            unset($format);
        };

        if ($type === 'contract-amendment')
        {
            $this->SetFont('DejaVuSerif', '', '8');
            $this->Ln();
            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Ustanovení smlouvy (ve znění případných předchozích dodatků) nedotčená tímto dodatkem zůstávají beze změn.'));
            $this->Ln();
            $this->Ln();
            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Tento dodatek je vyhotoven ve dvou stejnopisech.'));
            $this->Ln();
        }

        if ($type === 'contract-new' || $type === 'contract-new-x')
        {
            // cross
            $this->Ln(5);
            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY()); // --
            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,285); // \
            $this->Line($this->GetX(),285,$this->GetX()+187,$this->GetY()); // /
            $this->Line($this->GetX(),$this->GetY(),$this->GetX(),285); // |
            $this->Line($this->GetX()+187,$this->GetY(),$this->GetX()+187,285); // |
            $this->Line($this->GetX(),285,$this->GetX()+187,285); // --

            // add a page
            $this->AddPage();

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Ln();
            $this->Ln();
            $this->Write(4 ,iconv('UTF-8', 'UTF-8', 'Poskytnutá zařízení, aktivační poplatek a náhrada nákladů spojených s telekomunikačními zařízeními poskytnutými Uživateli za zvýhodněných podmínek'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(1);

            if (count($contract->borrowed_equipments) > 0)
            {
                $this->SetFont('DejaVuSerif', '', '8');
                if ($type === 'contract-new')
                    $this->Write(4, iconv('UTF-8', 'UTF-8', 'Poskytovatel poskytne Uživateli pro dobu trvání této smlouvy bezúplatně tato zařízení:'));
                else
                    $this->Write(4, iconv('UTF-8', 'UTF-8', 'Na základě uvedené předchozí smlouvy ze dne ' . $contract->conclusion_date . ' poskytl Poskytovatel Uživateli bezúplatně tato zařízení:'));

                $this->Ln(5);
                
                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->Cell(4, 5);
                $this->Cell(130, 5, iconv('UTF-8', 'UTF-8', 'Zařízení'), 1);
                $this->Cell(30, 5, iconv('UTF-8', 'UTF-8', 'Hodnota'), 1, 0, 'R');
                $this->Ln();                                    

                $this->SetFont('DejaVuSerif', '', '8');
                foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                    $this->Cell(4, 5);
                    $this->Cell(130, 5, iconv('UTF-8', 'UTF-8', $borrowed_equipment->equipment_type->name), 1);
                    $this->Cell(30, 5, iconv('UTF-8', 'UTF-8', $borrowed_equipment->equipment_type->price . ',- Kč'), 1, 0, 'R');
                    $this->Ln();                                    
                }

                $this->Ln();

                if ($type === 'contract-new-x')
                {
                    $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Smluvní strany ujednávají, že Poskytovatel Uživateli touto smlouvou uvedená zařízení nadále poskytuje k bezúplatnému užívání až do zániku této nové smlouvy.' . PHP_EOL), 0, 'J');
                    $this->Ln(3);
                }

                $this->SetFont('DejaVuSerif', 'B', '8');
                $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel je srozuměn se skutečností, že při bezúplatném poskytnutí zařízení Poskytovatel neumožňuje změnu tarifu na tarif, který má dle Ceníku nižší měsíční cenu než 295,- Kč.' . PHP_EOL), 0, 'J');
                $this->Ln(3);

                $this->SetFont('DejaVuSerif', '', '8');
                $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel je povinen tato zařízení Poskytovateli vrátit bez zbytečných odkladů nejpozději po zániku této Smlouvy.' . PHP_EOL), 0, 'J');
                $this->Ln(3);

                $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Náklady spojené s instalací dalších zařízení nebo další kabeláže se řídí aktuálně účinným Ceníkem Poskytovatele.' . PHP_EOL), 0, 'J');
                $this->Ln(3);

                if ($type === 'contract-new' && $contract->service_type->activation_fee > 0)
                {
                    $this->SetFont('DejaVuSerif', 'B', '8');

                    if ($contract->minimum_duration <= 0)
                    {
                        $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->service_type->activation_fee . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL), 0, 'J');
                        $this->Ln(3);
                    }
                    else
                    {
                        $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->service_type->activation_fee_with_obligation . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací a instalaci Poskytnutých zařízení.' . PHP_EOL), 0, 'J');
                        $this->Ln(3);
                        $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Poskytnutá zařízení jsou Uživateli poskytnuta Poskytovatelem za zvýhodněných podmínek (bezúplatně). V případě zániku této smlouvy před uplynutím '. $this->contractDurationBefore($contract->minimum_duration) . ' od jejího uzavření je proto Uživatel povinen nahradit Poskytovateli náklady spojené s výše uvedenými Poskytnutými zařízeními, a to v paušální částce ' . ($contract->service_type->activation_fee - $contract->service_type->activation_fee_with_obligation) . ',- Kč (' . $contract->service_type->activation_fee . ',- Kč je aktivační poplatek při smlouvě bez úvazku).' . PHP_EOL), 0, 'J');
                        $this->Ln(3);
                    }
                }
            }
            else
            {
                $this->SetFont('DejaVuSerif', '', '8');

                if ($type === 'contract-new' && $contract->service_type->activation_fee > 0)
                {
                    $this->SetFont('DejaVuSerif', 'B', '8');

                    if ($contract->minimum_duration <= 0)
                    {
                        $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->service_type->activation_fee . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL), 0, 'J');
                        $this->Ln(3);
                    }
                    else
                    {
                        $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel se zavazuje uhradit Poskytovateli aktivační poplatek ve výši ' . $contract->service_type->activation_fee_with_obligation . ',- Kč zahrnující náklady na zřízení koncového bodu Poskytovatelovy sítě elektronických komunikací.' . PHP_EOL), 0, 'J');
                        $this->Ln(3);
                        $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Aktivační poplatek je Uživateli poskytnut Poskytovatelem za zvýhodněných podmínek. V případě zániku této smlouvy před uplynutím ' . $this->contractDurationBefore($contract->minimum_duration) . ' od jejího uzavření je proto Uživatel povinen nahradit Poskytovateli náklady spojené se zřízením koncového bodu Poskytovatelovy sítě elektronických komunikací, a to v paušální částce ' . ($contract->service_type->activation_fee - $contract->service_type->activation_fee_with_obligation) . ',- Kč (' . $contract->service_type->activation_fee . ',- Kč je aktivační poplatek při smlouvě bez úvazku).' . PHP_EOL), 0, 'J');
                        $this->Ln(3);
                    }
                }

                $this->SetFont('DejaVuSerif', '', '8');
                $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Cena za případnou instalaci Uživatelových zařízení včetně případných souvisejících nákladů (např. kabeláž) se řídí aktuálním Ceníkem Poskytovatele.' . PHP_EOL), 0, 'J');
                $this->Ln(3);
            }

            /*                        
            // set the source file
            $pageCount = $this->setSourceFile("../smlouva-cast_druha.pdf");

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // add a page
                $this->AddPage();
                // import page 1
                $tplIdx = $this->importPage($pageNo);
                // use the imported page and place it at point 10,10 with a width of 100 mm
                $this->useTemplate($tplIdx, 0, 0);
            }               
            */

            $this->SetFont('DejaVuSerif', 'B', '9');
            $this->Write(4 ,iconv('UTF-8', 'UTF-8', 'Závěrečná ustanovení'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8',
                'Uživatel prohlašuje, že se podrobně seznámil s obsahem aktuálně účinných Všeobecných podmínek služeb elektronických komunikací (dále jako "Podmínky") a potvrzuje, že je od Poskytovatele obdržel a s jejich obsahem plně souhlasí.'
//                                'Uživatel prohlašuje, že se podrobně seznámil s obsahem aktuálně účinných Všeobecných podmínek služeb elektronických komunikací ze dne 22.2.2021 i s obsahem Všeobecných podmínek služeb elektronických komunikací ze dne 23.03.2021 účinných od 01.05.2021 (dále společně jako "Podmínky") a potvrzuje, že je od Poskytovatele obdržel a s jejich obsahem plně souhlasí.'
                . ' Je si vědom skutečnosti, že Podmínky jsou nedílnou součástí této smlouvy jako příloha č. 3 a zavazuje se je dodržovat.'
//                                . ' Je si vědom skutečnosti, že Podmínky jsou nedílnou součástí této smlouvy jako příloha č. 3 a č. 4 a zavazuje se je dodržovat.'
                . ' Je mu též známo, že Poskytovatel je oprávněn Podmínky v souladu s příslušnými právními předpisy jednostranně měnit.'
                . ' Podmínky obsahují mimo jiné i podrobné informace vyžadované § 63 odst. 1 zákona č. 127/2005 Sb. o elektronických komunikacích,'
                . ' jako jsou informace o veškerých podmínkách omezujících přístup k poskytovaným službám a možnostem jejich využívání,'
                . ' o minimální nabízené a minimální zaručené úrovni kvality poskytovaných služeb, o omezeních týkajících se omezení užívání koncových zařízení nebo o možnostech ukončení smlouvy.' . PHP_EOL), 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel uděluje Poskytovateli souhlas se zpracováním svých osobních údajů. Informace pro subjekt osobních údajů je přílohou č. 1 této smlouvy.' . PHP_EOL), 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel prohlašuje, že se podrobně seznámil s aktuálně účinným Ceníkem Poskytovatele, který je volně dostupný na Webu.' . PHP_EOL), 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Uživatel prohlašuje, že se podrobně seznámil s aktuálně účinným „Přehledem parametrů a rychlostí poskytovaných tarifů pro služby připojení k internetu v pevném místě“, který je nedílnou součástí této smlouvy jako příloha č. 2.' . PHP_EOL), 0, 'J');
            $this->Ln(3);
            $this->MultiCell(180, 4, iconv('UTF-8', 'UTF-8', 'Tato smlouva (č. ' . $contract->number . ') je vyhotovena ve dvou stejnopisech.' . PHP_EOL), 0, 'J');
            $this->Ln(3);

            $this->Write(4, iconv('UTF-8', 'UTF-8', 'Přílohy:'));
            $this->Ln();
            $this->Write(4, iconv('UTF-8', 'UTF-8', '   1) Souhlas se zpracováním osobních údajů'));
            $this->Ln();
            $this->Write(4, iconv('UTF-8', 'UTF-8', '   2) Přehled parametrů a rychlostí poskytovaných tarifů pro služby přístupu k internetu'));
            $this->Ln();
            $this->Write(4, iconv('UTF-8', 'UTF-8', '   3) Všeobecné podmínky služeb elektronických komunikací v aktuálním znění'));
            $this->Ln();
//            $this->Write(4, iconv('UTF-8', 'UTF-8', '   4) Všeobecné podmínky služeb elektronických komunikací platné od 01.05.2021'));
//            $this->Ln();
        }

        $this->SetFont('DejaVuSerif', '', '8');
        if ($this->GetY() > 240) $this->AddPage();
        $this->Ln();
        $this->Ln();
        if ($signed) 
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ' . new \Cake\I18n\FrozenDate()), '', '', 'C');
        else
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ____________________'), '', '', 'C');

        $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ____________________'), '', '', 'C');

        $this->Ln(20);

        $this->Cell(90,4, '......................................................', '', '', 'C');
        $this->Cell(90,4, '......................................................', '', '', 'C');
        $this->Ln();
        $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Poskytovatel'), '', '', 'C');
        $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Uživatel'), '', '', 'C');
        $this->Ln();

        if ($signed) $this->Image(K_PATH_IMAGES . 'signature.png', 38, $this->GetY()-19, 35);

        $this->Close();
    }

    function GenerateGDPRAgreement($data, $type = 'gdpr-new', $signed = false)
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        //$address_types variable from config
        global $address_types;

        //$this->Open(); //no in new version

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png',15,5,40);

        $this->SetFont('DejaVuSerif', 'BI', '8');
        $this->Ln(9);

        $this->Cell(135,4, '');
        $this->Cell(20,4, 'tel:', '', '', 'R');
        $this->Cell(40,4, '+420 488 572 050');
        $this->Ln();

        $this->Cell(135,4, '');
        $this->Cell(20,4, 'mobil:', '', '', 'R');
        $this->Cell(40,4, '+420 604 553 444');
        $this->Ln();

        $this->Cell(135,4, '');
        $this->Cell(20,4, 'e-mail:', '', '', 'R');
        $this->Cell(40,4, 'mail@netair.cz');
        $this->Ln();

        $this->Ln(2);

        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
        $this->Ln(0.4);
        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
        $this->Ln(2);

        $this->SetFont('DejaVuSerif', 'B', '18');
        $this->Cell(187,6,iconv('UTF-8', 'UTF-8', 'SOUHLAS'), '', '', 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', '12');
        $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'se zpracováním osobních údajů'), '', '', 'C');
        $this->Ln(3);

        $this->Ln(4);
        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
        $this->Ln(1);

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'nový / změna:'), '', '', 'C');
        $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'číslo souhlasu:'), '', '', 'C');
        $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'doba trvání souhlasu:'), '', '', 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', '8');
        switch ($type) {
        case 'gdpr-new':
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'nový'), '', '', 'C');
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', $data['varsym']), '', '', 'C');
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'na dobu neurčitou'), '', '', 'C');
            break;
        case 'gdpr-change':
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'změna'), '', '', 'C');
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', $data['varsym']), '', '', 'C');
            $this->Cell(62,4,iconv('UTF-8', 'UTF-8', 'na dobu neurčitou'), '', '', 'C');
            break;
        };
        $this->Ln();

        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
        $this->Ln();

        $this->Ln(2);
        $this->SetFont('DejaVuSerif', 'B', '12');
        $this->Cell(187,2,iconv('UTF-8', 'UTF-8', 'uzavřený mezi'), '', '', 'C');
        $this->Ln();

        $this->Ln(2);
        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'Správcem:'));
        $this->Ln();

        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

        $this->Ln();
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4, 'NETAIR, s.r.o.');
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4, '');
        $this->Ln();

        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4,iconv('UTF-8', 'UTF-8', 'Jablonec nad Jizerou 299'));
        $this->Cell(20,4, '', '', '', 'R');
        $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'IČ:'));
        $this->Cell(40,4, '27496139');
        $this->Ln();

        $this->Cell(30,4, '', '', '', 'R');
        $this->Cell(40,4, '512 43 Jablonec nad Jizerou');
        $this->Cell(20,4, '', '', '', 'R');
        $this->Cell(10,4,iconv('UTF-8', 'UTF-8', 'DIČ:'));
        $this->Cell(40,4, 'CZ27496139');
        $this->Ln();

        $this->Ln(4);
        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4, '');
        $this->Cell(100,4,iconv('UTF-8', 'UTF-8', 'zastoupeným Marko Jujnovićem, jednatelem'));
        $this->Ln();
        $this->Cell(30,4, '');
        $this->Cell(100,4,iconv('UTF-8', 'UTF-8', 'zapsaným v obchodním rejstříku vedeném u Krajského soudu v Hradci Králové, oddíl C, vložka 22450.'));
        $this->Ln();
        $this->Ln();

        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

        $this->SetFont('DejaVuSerif', 'B', '9');
        $this->Ln();
        $this->Cell(187,4,iconv('UTF-8', 'UTF-8', 'a'), '', '', 'C');
        $this->Ln();
        $this->Cell(45,4,iconv('UTF-8', 'UTF-8', 'Uživatelem:'));
        $this->Ln();

        $this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());

        foreach ($data['addresses'] as $address)
        {
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $address_types[$address['type']] . ':'), '', '', 'L');
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'firma:'), '', '', 'R');
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
                $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'jméno:'), '', '', 'R');
            else
                $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'zastoupená:'), '', '', 'R');

            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $name));
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'ulice / č.p.:'), '', '', 'R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $street));
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', '8');
            $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'PSČ / město:'), '', '', 'R');
            $this->SetFont('DejaVuSerif', 'B', '8');
            $this->Cell(60,4,iconv('UTF-8', 'UTF-8', substr($address["zip"],0,3) . ' ' . substr($address["zip"],3,2) . ' ' . $address['city']));
            $this->Ln();
            $this->Ln();
        }

        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60,4,iconv('UTF-8', 'UTF-8', 'Ostatní údaje:'), '', '', 'L');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'datum narození:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['date_of_birth']));
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'číslo OP:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['identity_card_number']));
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'IČ:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['ic']));
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'DIČ:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(70,4,iconv('UTF-8', 'UTF-8', $data['dic']));
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'tel:'), '', '', 'R');
        $this->SetFont('DejaVuSerif', 'B', '8');
        $this->Cell(60,4,iconv('UTF-8', 'UTF-8', $data['phone']));
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Cell(30,4,iconv('UTF-8', 'UTF-8', 'e-mail:'), '', '', 'R');
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

        //$pdf->SetFont('ZapfDingbats', '', 10);
        //$pdf->Cell(10, 10, 4, 1, 0);

        $this->SetFont('DejaVuSerif', '', '8');
        $this->Ln();
        $this->Ln();
        if ($signed) 
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ' . $data['now']), '', '', 'C');
        else
            $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ____________________'), '', '', 'C');

        $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Datum podpisu: ____________________'), '', '', 'C');

        $this->Ln(20);

        $this->Cell(90,4, '......................................................', '', '', 'C');
        $this->Cell(90,4, '......................................................', '', '', 'C');
        $this->Ln();
        $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Správce'), '', '', 'C');
        $this->Cell(90,4,iconv('UTF-8', 'UTF-8', 'Uživatel'), '', '', 'C');
        $this->Ln();

        if ($signed) $this->Image(K_PATH_IMAGES . 'signature.png', 38, $this->GetY()-19, 35);

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

default:
    exit;
}
?>