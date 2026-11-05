<?php
declare(strict_types=1);

namespace App\BulkMessages;

use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessagePurpose;
use App\Model\Enum\CustomerMessageType;
use App\NMS\ApiClient as NMSApiClient;
use App\Service\OperatorReport;
use Cake\Mailer\Mailer;

/**
 * What the operator is told about a bulk message once it has been queued.
 *
 * @phpstan-type SendSummary array{
 *     sent: int,
 *     channel: string,
 *     is_sms: bool,
 *     purpose: string,
 *     subject: string,
 *     body: string,
 *     filters: list<string>,
 *     ignored_customer_consent: bool,
 *     ignored_contact_use: bool,
 *     groups: list<array{
 *         ap_id: string|null,
 *         ap_name: string,
 *         customers: list<array{
 *             number: string|null,
 *             name: string,
 *             contract_number: string|null,
 *             services: list<string>,
 *             vip: bool,
 *             criticality: string|null,
 *             recipients: list<string>
 *         }>
 *     }>,
 *     skipped: list<array{id: string, number: string|null, name: string}>,
 *     dropped: list<array{number: string|null, name: string}>,
 *     flagged: array{vip: int, critical: int},
 *     summary_mailed: bool
 * }
 */
final class BulkSendSummary
{
    /**
     * Assemble the record of what a bulk send did: the message itself, the
     * filters that picked the recipients, and every recipient grouped by access
     * point with their services, flags and the addresses the message went to.
     *
     * The same structure feeds the done step and the summary e-mail, so both
     * always tell the same story.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array{purpose?: int, filters?: array<string, mixed>, preview?: list<string>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
     * @param \App\BulkMessages\BulkRecipientFilterRegistry $registry Filter registry.
     * @param \App\Model\Entity\CustomerMessage $customerMessage Composed message.
     * @param list<\App\Model\Entity\Customer> $messaged Customers a message was built for.
     * @param list<\App\Model\Entity\CustomerMessage> $customerMessages Their messages, in the same order.
     * @param list<array{id: string, number: string|null, name: string}> $skipped Selected but without a usable contact.
     * @param list<array{number: string|null, name: string}> $dropped Selected but no longer eligible.
     * @return SendSummary
     */
    public function build(
        CustomerMessagePurpose $purpose,
        array $state,
        BulkRecipientFilterRegistry $registry,
        CustomerMessage $customerMessage,
        array $messaged,
        array $customerMessages,
        array $skipped,
        array $dropped,
    ): array {
        // the entity normalises contacts into plain addresses, which is exactly
        // what the report should show
        $recipientsByCustomer = [];
        foreach ($customerMessages as $index => $message) {
            $customer = $messaged[$index] ?? null;
            if ($customer !== null) {
                $recipientsByCustomer[(string)$customer->id] = array_values(
                    array_map('strval', $message->recipients),
                );
            }
        }

        $apNames = NMSApiClient::getAccessPointsList(onlyActive: false)->or([]);
        $groups = [];
        foreach ((new BulkRecipients())->groupByAccessPoint($messaged, $apNames) as $group) {
            $customers = [];
            foreach ($group['rows'] as $row) {
                $customers[] = [
                    'number' => $row['customer']->number,
                    'name' => $row['customer']->name,
                    'contract_number' => $row['contract']?->number,
                    'services' => $row['services'],
                    'vip' => $row['vip'],
                    'criticality' => $row['criticality']?->label(),
                    'recipients' => $recipientsByCustomer[(string)$row['customer']->id] ?? [],
                ];
            }

            $groups[] = [
                'ap_id' => $group['ap_id'],
                'ap_name' => $group['ap_name'],
                'customers' => $customers,
            ];
        }

        return [
            'sent' => count($customerMessages),
            'channel' => $customerMessage->type->label(),
            'is_sms' => $customerMessage->type === CustomerMessageType::Sms,
            'purpose' => $purpose->label(),
            'subject' => (string)$customerMessage->subject,
            'body' => (string)$customerMessage->body,
            'filters' => $registry->describeFilters($purpose, $state['filters'] ?? []),
            'ignored_customer_consent' => ($state['ignore_customer_consent'] ?? false) === true,
            'ignored_contact_use' => ($state['ignore_contact_use'] ?? false) === true,
            'groups' => $groups,
            'skipped' => $skipped,
            'dropped' => $dropped,
            'flagged' => (new BulkRecipients())->countFlagged($messaged),
            // the caller flips this once the summary has actually gone out
            'summary_mailed' => false,
        ];
    }

    /**
     * Render the send report as the plain-text body of the summary e-mail.
     *
     * @param SendSummary $report Report to render.
     * @return string
     */
    public function render(array $report): string
    {
        $lines = [
            __('Purpose: {0}', $report['purpose']),
            __('Channel: {0}', $report['channel']),
            __('{0} message(s) queued for sending.', $report['sent']),
            '',
            __('Recipient filters:'),
        ];

        $lines = array_merge(
            $lines,
            $report['filters'] === []
                ? ['- ' . __('No filter was applied.')]
                : array_map(static fn(string $filter): string => '- ' . $filter, $report['filters']),
        );

        if ($report['ignored_customer_consent']) {
            $lines[] = '- ' . __('Customer mailing consent was ignored.');
        }
        if ($report['ignored_contact_use']) {
            $lines[] = '- ' . __('Per-contact routing flag was ignored.');
        }

        $lines[] = '';
        $lines[] = __('Recipients:');
        foreach ($report['groups'] as $group) {
            $lines[] = '';
            $lines[] = $group['ap_name'] . ' (' . count($group['customers']) . ')';
            foreach ($group['customers'] as $customer) {
                $flags = [];
                if ($customer['vip']) {
                    $flags[] = __('VIP');
                }
                if ($customer['criticality'] !== null) {
                    $flags[] = $customer['criticality'];
                }

                $lines[] = '  ' . implode(' | ', array_filter([
                    trim($customer['number'] . ' ' . $customer['name']),
                    $customer['contract_number'],
                    implode(', ', $customer['services']),
                    implode(', ', $customer['recipients']),
                    $flags === [] ? null : '!! ' . implode(', ', $flags),
                ]));
            }
        }

        foreach (
            [
                __('Not sent — no usable contact:') => $report['skipped'],
                __('Not sent — no longer eligible when sending:') => $report['dropped'],
            ] as $heading => $customers
        ) {
            if ($customers === []) {
                continue;
            }

            $lines[] = '';
            $lines[] = $heading;
            foreach ($customers as $customer) {
                $lines[] = '  ' . trim($customer['number'] . ' ' . $customer['name']);
            }
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 60);
        $lines[] = __('Subject: {0}', $report['subject']);
        $lines[] = '';
        $lines[] = $report['body'];

        return implode(PHP_EOL, $lines);
    }

    /**
     * Who gets the summary: whoever sent the message, and the addresses the operators are told at.
     *
     * @param string|null $sender The address of whoever sent it.
     * @return list<string>
     */
    public function recipients(?string $sender): array
    {
        $addresses = $sender !== null && $sender !== '' ? [$sender] : [];

        foreach (OperatorReport::recipients() as $address) {
            if (!in_array($address, $addresses, true)) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * Mails the summary.
     *
     * @param SendSummary $report The summary.
     * @param list<string> $addresses Who gets it.
     * @return void
     */
    public function mail(array $report, array $addresses): void
    {
        $mailer = new Mailer('default');
        foreach ($addresses as $address) {
            $mailer->addTo($address);
        }

        $mailer->setSubject(__(
            'Bulk customer message: {0} — {1} message(s) sent',
            $report['purpose'],
            $report['sent'],
        ));

        $mailer->deliver($this->render($report));
    }
}
