<?php
declare(strict_types=1);

namespace App\Model\Audit;

use ArrayObject;
use AuditStash\Model\Behavior\AuditLogBehavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Utility\Text;
use SplObjectStorage;

/**
 * One entry in the audit log for everything a transaction writes.
 *
 * Audit-stash queues what it is going to log and writes the queue out on
 * `Model.afterSaveCommit`, which CakePHP dispatches only for a save that owns its transaction.
 * Every save inside `Connection::transactional()` is therefore queued and never written, so the
 * act is missing from the log altogether rather than merely split up. The queue has to be handed
 * to each write and emptied by hand once the transaction has committed.
 *
 * Sharing one trail is also what makes the writes one entry instead of several, which is the
 * point of it: applying a proposal touches the billings, the version, the contract and the
 * proposal itself, and that is one thing somebody did.
 */
final class AuditTrail
{
    /**
     * What the writes queue up until it is written out.
     *
     * @var \SplObjectStorage<\Cake\Datasource\EntityInterface, \AuditStash\Event\BaseEvent>
     */
    private SplObjectStorage $queue;

    /**
     * What ties the writes together in the log.
     */
    private string $transaction;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->queue = new SplObjectStorage();
        $this->transaction = Text::uuid();
    }

    /**
     * Added to the options of every save and delete the transaction makes.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            '_auditQueue' => $this->queue,
            '_auditTransaction' => $this->transaction,
        ];
    }

    /**
     * Writes the queue out, once, after the transaction has committed.
     *
     * Any audited table the transaction touched will do: the queue holds the events of all of
     * them and the table only says who dispatches and persists.
     *
     * @param \Cake\ORM\Table $table An audited table the transaction wrote to.
     * @param \Cake\Datasource\EntityInterface $entity What the act was about.
     * @return void
     */
    public function flush(Table $table, EntityInterface $entity): void
    {
        $behaviors = $table->behaviors();
        $behavior = $behaviors->has('AuditLog') ? $behaviors->get('AuditLog') : null;

        if (!$behavior instanceof AuditLogBehavior) {
            // Said out loud rather than thrown: the transaction has committed by now, so the
            // records are right and only the log is missing. Every table of ours is audited, so
            // a trail handed one that is not is a mistake in the caller, not a state to handle.
            Log::warning(sprintf(
                'The audit trail was flushed through %s, which keeps no audit log.',
                $table->getRegistryAlias(),
            ));

            return;
        }

        // The event name is the behaviour's to recognise, not ours to make up.
        $behavior->afterCommit(
            new Event('Model.afterCommit', $table),
            $entity,
            new ArrayObject($this->options()),
        );

        // The behaviour empties the copy it was handed rather than this one, and a second flush
        // would write everything a second time.
        $this->queue = new SplObjectStorage();
    }
}
