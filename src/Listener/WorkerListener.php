<?php
declare(strict_types=1);

namespace App\Listener;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Log\LogTrait;
use Cake\Queue\Job\Message;
use Psr\Log\LogLevel;

class WorkerListener implements EventListenerInterface
{
    use LogTrait;

    /**
     * Returns Implemented Events
     */
    public function implementedEvents(): array
    {
        return [
            'Processor.message.exception' => 'processorMessageException',
            'Processor.message.invalid' => 'processorMessageInvalid',
            'Processor.message.reject' => 'processorMessageReject',
            'Processor.message.success' => 'processorMessageSuccess',
            'Processor.maxIterations' => 'processorMaxIterations',
            'Processor.maxRuntime' => 'processorMaxRuntime',
            'Processor.message.failure' => 'processorMessageFailure',
            'Processor.message.seen' => 'processorMessageSeen',
            'Processor.message.start' => 'processorMessageStart',
        ];
    }

    /**
     * Log Message Exception
     */
    public function processorMessageException(Event $event, Message $message): void
    {
        $this->log(__METHOD__);
        $this->log(print_r($event, true));
        $this->log($message);
    }

    /**
     * Log Message Invalid
     */
    public function processorMessageInvalid(Event $event): void
    {
        $this->log(__METHOD__);
    }

    /**
     * Log Message Rejected
     */
    public function processorMessageReject(Event $event): void
    {
        $this->log(__METHOD__);
    }

    /**
     * Log Message Success
     */
    public function processorMessageSuccess(Event $event): void
    {
        $this->log(__METHOD__, LogLevel::NOTICE);
    }

    /**
     * Log Max Iteration
     */
    public function processorMaxIterations(): void
    {
        $this->log(__METHOD__);
    }

    /**
     * Log Max Runtime
     */
    public function processorMaxRuntime(): void
    {
        $this->log(__METHOD__);
    }

    /**
     * Log Message Failure
     */
    public function processorMessageFailure(Event $event): void
    {
        $this->log(__METHOD__);
    }

    /**
     * Log Message Seen
     */
    public function processorMessageSeen(Event $event): void
    {
        $this->log(__METHOD__, LogLevel::NOTICE);
    }

    /**
     * Log Message Start
     */
    public function processorMessageStart(Event $event): void
    {
        $this->log(__METHOD__, LogLevel::NOTICE);
    }
}
