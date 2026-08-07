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
            // One row per account, its latest session, read out of `radacct_user_start_idx` for
            // the accounts in hand. Aggregating the whole of `radacct` instead costs the page
            // more the longer the accounting data gets. Needs the 2004 runbook.
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
