<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Http\Answer;
use App\NMS\ApiClient as NMSApiClient;
use App\Phones\Formatter as PhoneFormatter;
use Cake\Core\Configure;
use Override;
use Tasks\Model\Entity\Task as TasksTask;

/**
 * Task Entity
 *
 * On top of the shared task: what this application files a task under, and the line that reads
 * it out.
 *
 * @property string|null $customer_id
 * @property string|null $contract_id
 * @property string|null $access_point_id
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Http\Answer<\App\NMS\Dto\AccessPoint|null> $access_point
 */
class Task extends TasksTask
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'task_state_id' => true,
        'task_type_id' => true,
        'subject' => true,
        'text' => true,
        'priority' => true,
        'customer_id' => true,
        'contract_id' => true,
        'user_id' => true,
        'email' => true,
        'phone' => true,
        'start_date' => true,
        'finish_date' => true,
        'estimated_date' => true,
        'critical_date' => true,
        'access_point_id' => true,
        'task_state' => true,
        'task_type' => true,
        'customer' => true,
        'contract' => true,
        'user' => true,
        'collaborators' => true,
        'access_point' => true,
    ];

    /**
     * getter for acess point (try to load via ApiClient)
     *
     * @return \App\Http\Answer<\App\NMS\Dto\AccessPoint|null>
     */
    protected function _getAccessPoint(): Answer
    {
        if ($this->access_point_id === null) {
            return Answer::notAsked();
        }

        return NMSApiClient::getAccessPoint($this->access_point_id);
    }

    /**
     * The one line that says what a task is about: who it is for, where, and how to reach
     * them.
     *
     * Whoever already shows the subject - a listing that has it as its heading, say - asks
     * for it to be left out rather than reading it twice.
     *
     * @param bool $with_subject Whether the subject heads the line.
     * @return string
     */
    #[Override]
    public function getSummaryText(bool $with_subject = true): string
    {
        $phoneNumber = $this->phone;
        if (isset($phoneNumber) && Configure::read('Phones.stripPrefixForSummary') === true) {
            $phoneNumber = PhoneFormatter::toLocal($phoneNumber);
        }

        // The contract's address wins over the customer's one.
        $address = $this->contract->installation_address
            ?? $this->customer->installation_address
            ?? null;

        // The subject and the customer head the line, the rest follows behind commas.
        $summary_text = implode(', ', array_filter([
            implode(' - ', array_filter([
                $with_subject ? $this->subject ?? $this->task_type->name ?? null : null,
                $this->customer->company ?? $this->customer->last_name ?? null,
            ])),
            $address?->street_and_number,
            $address?->city,
            $phoneNumber,
        ]));

        $number = $this->contract->number ?? $this->customer->number ?? null;

        return $number !== null ? $summary_text . ' (' . $number . ')' : $summary_text;
    }
}
