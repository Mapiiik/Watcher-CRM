<?php
declare(strict_types=1);

namespace App\Pdf\Trait;

use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\CustomerLegalStatus;
use App\Service\ContractPrint\ContractPrintData;
use Settings\Utility\Settings;

/**
 * Who is told about withdrawing, and the notice with its form.
 *
 * The contract tells the customer above the signatures. The notice and the form go on a page of
 * their own after the summary, which is printed once for the customer and handed over before the
 * contract is made, rather than with the contract, which is printed in two copies.
 */
trait WithdrawalNoticeTrait
{
    /**
     * What the notice is set in: the face of the contract wherever it is printed, because at the ten
     * points of the summary a notice as long as the Croatian one runs onto a second page.
     */
    protected const NOTICE_FONT = 'DejaVuSerif';
    protected const NOTICE_FONT_SIZE = 8.0;

    /**
     * Whether the paper makes or changes a contract, which is what one can withdraw from.
     *
     * The summary counts, as it is printed only for a contract or an amendment on offer.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return bool
     */
    protected function makesOrChangesTheContract(ContractPrintData $data): bool
    {
        return in_array($data->type, [
            ContractDocumentType::ContractNew,
            ContractDocumentType::ContractNewX,
            ContractDocumentType::ContractAmendment,
            ContractDocumentType::ContractSummary,
        ], true);
    }

    /**
     * Whether the customer is told about withdrawing, as who they are in law.
     *
     * Who may withdraw is the law of the deployment's to say: consumer law gives the right to a
     * natural person, and the Czech law on electronic communications to a sole trader too. So each
     * of the three is switched on or off, and a customer nobody has said anything about counts as
     * a natural person.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return bool
     */
    protected function toldAboutWithdrawal(ContractPrintData $data): bool
    {
        if (!$this->makesOrChangesTheContract($data)) {
            return false;
        }

        $who = match ($data->contract->customer->legal_status) {
            CustomerLegalStatus::NaturalPerson, null => 'natural_persons',
            CustomerLegalStatus::SoleTrader => 'sole_traders',
            CustomerLegalStatus::LegalEntity => 'legal_entities',
        };

        return (bool)Settings::get('core.documents.contracts.contract.withdrawal_for.' . $who, false);
    }

    /**
     * The notice on withdrawal and the form for it, on a page of their own.
     *
     * A page of its own, because the form is meant to be cut off and sent back.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    protected function printWithdrawalNotice(ContractPrintData $data): void
    {
        $html = trim(Settings::getString('core.documents.contracts.contract.texts.withdrawal_attachment_html'));

        if ($html === '' || !$this->toldAboutWithdrawal($data)) {
            return;
        }

        $this->AddPage();

        $this->SetFont(self::NOTICE_FONT, '', self::NOTICE_FONT_SIZE);
        $this->setListIndentWidth(4);
        $this->writeHTML(strtr($html, array_map(
            fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_HTML5),
            $this->withdrawalFormDetails($data),
        )), true, false, false, true, '');
    }

    /**
     * What the withdrawal notice and form can be filled in with already.
     *
     * The provider and where to send it, the customer as the contract names them, at its billing
     * address, and the services it provides. When they ordered it is left to them: the paper is
     * printed before it is signed.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return array<string, string> Placeholder and what it stands for
     */
    private function withdrawalFormDetails(ContractPrintData $data): array
    {
        $address = $data->contract->billing_address;

        $services = [];
        foreach ($data->activeBillings as $billing) {
            if ($billing->name !== '' && !in_array($billing->name, $services, true)) {
                $services[] = $billing->name;
            }
        }

        $company = fn(string $key): string => Settings::getString('core.company.' . $key);

        return [
            '{company_name}' => $company('name'),
            '{company_address}' => $company('address'),
            '{contracts_phone}' => $company('contracts.phone'),
            '{contracts_email}' => $company('contracts.email'),
            '{contract_number}' => (string)$data->contract->number,
            '{customer_name}' => $address->full_name,
            '{customer_address}' => implode(', ', array_filter([
                $address->street_and_number_extra,
                $address->zip_and_city,
            ])),
            '{services}' => implode(', ', $services),
        ];
    }
}
