<?php
declare(strict_types=1);

namespace Tasks\Dashboard\Card;

use App\Model\Table\TasksTable;
use Cake\Database\Expression\FunctionExpression;
use Cake\ORM\Query\SelectQuery;
use Dashboard\Card\AbstractDashboardCard;

/**
 * Shared ground for the cards that list tasks.
 *
 * The listing stays deliberately shallow - only what the card draws is joined in, so a
 * card costs one query. The addresses that make up a task's summary line are left to the
 * task listing itself.
 */
abstract class AbstractTaskListCard extends AbstractDashboardCard
{
    /**
     * The roles that work with tasks at all.
     *
     * @var list<string>
     */
    protected const TASK_ROLES = [
        'customer-service-technician',
        'network-technician',
        'network-manager',
        'sales-representative',
        'sales-manager',
        'bookkeeper',
    ];

    /**
     * @param \App\Model\Table\TasksTable $tasks Tasks table.
     */
    public function __construct(protected TasksTable $tasks)
    {
    }

    /**
     * The task cards differ in what they select, not in how they are drawn.
     *
     * @return string
     */
    public function template(): string
    {
        return 'task_list';
    }

    /**
     * The rows to draw plus how many there are in all, so a card can point past the few
     * it has room for.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query The tasks this card selects.
     * @param array<string, mixed> $filter The listing filter that reproduces this card.
     * @param array<string, mixed> $extra Anything else the card's wording needs.
     * @return array<string, mixed>
     */
    protected function payload(SelectQuery $query, array $filter, array $extra = []): array
    {
        $total = $query->count();

        return [
            'tasks' => $query->limit($this->maximumRows())->all(),
            'total' => $total,
            'url' => $this->listingUrl($filter),
        ] + $extra;
    }

    /**
     * The task listing narrowed to what this card holds.
     *
     * The listing keeps its filter in the session, so every field the card cares about is
     * named rather than left out - otherwise whatever the operator last filtered by would
     * still be narrowing the listing the card points at.
     *
     * @param array<string, mixed> $filter What this card narrows by.
     * @return array<string, mixed>
     */
    protected function listingUrl(array $filter): array
    {
        return [
            'controller' => 'Tasks',
            'action' => 'index',
            'customer_id' => false,
            '?' => $filter + [
                'user_id' => '',
                'pressing' => 0,
                'stale' => 0,
                'show_completed' => 0,
                'task_type_id' => '',
                'task_state_id' => '',
                'access_point_id' => '',
                'search' => '',
            ],
        ];
    }

    /**
     * Unfinished tasks, most pressing first.
     *
     * PostgreSQL sorts nulls last on an ascending order, so tasks without a deadline fall
     * below the ones that have one.
     *
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task>
     */
    protected function activeTasks(): SelectQuery
    {
        /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query */
        $query = $this->tasks
            ->find('active')
            ->contain($this->tasks->summaryContain());

        // Whichever of the two dates comes first is the one a task is waiting on, so that is
        // what it is ordered by - ordering by the critical date alone would drop every task
        // that only carries an estimate to the bottom. PostgreSQL's `LEAST` passes over
        // nulls, which is what leaves a task with one date sorting by that one.
        $due = new FunctionExpression('LEAST', [
            'Tasks.critical_date' => 'identifier',
            'Tasks.estimated_date' => 'identifier',
        ]);

        return $query
            ->orderByDesc('Tasks.priority')
            ->orderByAsc($due)
            ->orderByDesc('Tasks.nid');
    }
}
