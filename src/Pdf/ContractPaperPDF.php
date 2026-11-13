<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Service\ContractPrint\ContractPrintData;
use InvalidArgumentException;

/**
 * What a contract and its handover protocols have in common.
 *
 * Both are printed from the same data and open the same way, with the same two parties and the
 * same table of who the customer is. What differs is what follows.
 */
abstract class ContractPaperPDF extends AppPDF
{
    /**
     * These are papers that get read in order and signed at the end, so a clause split across
     * the fold is read twice and a heading stranded at the foot reads as a mistake. Blocks
     * take the break with them instead.
     */
    protected const KEEPS_BLOCKS_WHOLE = true;

    /**
     * The contract version this document puts into effect.
     *
     * The guards above have already refused a document that needs one and has not got it. This
     * is where that promise becomes a type, so the sections below can rely on it.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return \App\Model\Entity\ContractVersion
     * @throws \InvalidArgumentException When there is no version to execute
     */
    protected function executedVersion(ContractPrintData $data): ContractVersion
    {
        if ($data->contractVersionToBeExecuted === null) {
            throw new InvalidArgumentException('The contract version to be executed must be provided');
        }

        return $data->contractVersionToBeExecuted;
    }

    /**
     * The contract version this document brings to an end.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return \App\Model\Entity\ContractVersion
     * @throws \InvalidArgumentException When there is no version to terminate
     */
    protected function terminatedVersion(ContractPrintData $data): ContractVersion
    {
        if ($data->contractVersionToBeTerminated === null) {
            throw new InvalidArgumentException('The contract version to be terminated must be provided');
        }

        return $data->contractVersionToBeTerminated;
    }

    /**
     * The line that introduces the two parties.
     *
     * @param string $text How this document names what it is between
     * @return void
     */
    protected function printBetween(string $text): void
    {
        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->printFullWidth($text, 2);
        $this->Ln();
    }

    /**
     * Who the customer is: their identity, their billing address, and wherever else the
     * service or the post has to reach them.
     *
     * The contract and its handover protocol state this identically, and they have to - the
     * protocol is signed on the doorstep against what the contract says.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    protected function printCustomerBlock(Contract $contract): void
    {
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTable(
            [
                __d('documents', 'Billing Address') . ':',
                '',
            ],
            [
                [
                    [
                        'label' => $this->label('company'),
                        'value' => $this->strOrX($contract->billing_address->company, ''),
                    ],
                    [
                        'label' => $this->label('birth_date'),
                        'value' => (string)$contract->customer->date_of_birth,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => $this->label('identity_number'),
                        'value' => $this->strOrX($contract->customer->identity_number),
                        'label_width' => 14,
                        'value_width' => 26,
                    ],
                ],
                [
                    [
                        'label' => is_null($contract->billing_address->company)
                            ? $this->label('name')
                            : $this->label('represented'),
                        'value' => $contract->billing_address->full_name,
                    ],
                    [
                        'label' => $this->label('identity_card_number'),
                        'value' => (string)$contract->customer->identity_card_number,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => $this->label('vat_number'),
                        'value' => $this->strOrX($contract->customer->vat_number),
                        'label_width' => 14,
                        'value_width' => 26,
                    ],
                ],
                [
                    [
                        'label' => $this->label('street'),
                        'value' => $contract->billing_address->street_and_number_extra,
                    ],
                    [
                        'label' => $this->label('phone'),
                        'value' => $contract->customer->phone,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
                [
                    [
                        'label' => $this->label('zip_city'),
                        'value' => $contract->billing_address->zip_and_city,
                    ],
                    [
                        'label' => $this->label('email'),
                        'value' => $contract->customer->email,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
            ],
        );

        $this->printAddressBlock(
            __d('documents', 'Installation Address'),
            $contract->installation_address->full_address ?? null,
        );
        $this->printAddressBlock(
            __d('documents', 'Delivery Address'),
            $contract->delivery_address->full_address ?? null,
        );
        $this->printAddressBlock(
            __d('documents', 'Permanent Address'),
            $contract->permanent_address->full_address ?? null,
        );
    }
}
