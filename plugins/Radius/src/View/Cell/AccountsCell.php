<?php
declare(strict_types=1);

namespace Radius\View\Cell;

use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Query\SelectQuery;
use Cake\View\Cell;
use Override;
use Radius\Model\Table\AccountsTable;

/**
 * Accounts cell
 */
class AccountsCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = ['show_contracts'];

    /**
     * Show contracts
     */
    protected bool $show_contracts = true;

    /**
     * Initialization logic run at the end of object construction.
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
    }

    /**
     * Default display method.
     *
     * @param array<mixed> $conditions Query conditions.
     * @return void
     */
    public function display(array $conditions = []): void
    {
        $contain = [
            // Only the latest session of each account is shown, and it is read straight out of
            // `radacct_user_start_idx` over the accounts in hand: the index leads with the
            // username and descends by the start time, so the first row of each account is the
            // one wanted.
            //
            // What stood here asked instead for the latest session of every username there has
            // ever been - `GROUP BY username` over the whole of `radacct` - and joined that back
            // to the three or four rows it needed. Over 422 000 sessions the page paid 780 ms for
            // it against 5 ms this way, and the old cost grew with the table while this one does
            // not. See the 2004 runbook for the index and the numbers.
            //
            // Where the two part is sessions that begin at the same instant, which the accounting
            // data does carry: the old form returned every session tied for the latest start,
            // this one returns a single row per account. The template only ever reads the first,
            // so the page is the same and the rest were fetched for nothing.
            'Radacct' => fn(SelectQuery $query): SelectQuery => $query
                ->distinct(['Radacct.username'])
                ->orderBy([
                    'Radacct.username' => 'ASC',
                    'Radacct.acctstarttime' => 'DESC',
                ]),
            'Radreply',
            'Radusergroup',
        ];

        if ($this->show_contracts) {
            $contain += [
                'Contracts' => [
                    'ContractStates',
                ],
            ];
        }

        try {
            //Try to load RADIUS accounts
            $accounts = $this->fetchTable(AccountsTable::class)
                ->find(
                    'all',
                    conditions: $conditions,
                    contain: $contain,
                    order: [
                        'Accounts.active' => 'DESC',
                        'Accounts.contract_id' => 'DESC',
                        'Accounts.username',
                    ],
                )
                ->all();
        } catch (MissingConnectionException) {
            //Couldn't connect
            $accounts = null;
        }

        $this->set(compact('accounts'));
        $this->set('show_contracts', $this->show_contracts);
    }
}
