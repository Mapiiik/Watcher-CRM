<?php
declare(strict_types=1);

namespace Radius\View\Cell;

use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Query\SelectQuery;
use Cake\View\Cell;

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
     *
     * @var bool
     */
    protected bool $show_contracts = true;

    /**
     * Initialization logic run at the end of object construction.
     *
     * @return void
     */
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
            'Radacct' => function (SelectQuery $q) {
                $subquery = $this->fetchTable('Radius.Radacct')->subquery()
                    ->select([
                        'username',
                        'max_acctstarttime' => $q->func()->max('acctstarttime'),
                    ])
                    ->groupBy([
                        'username',
                    ]);

                return $q
                    ->innerJoin(
                        ['RadacctLast' => $subquery],
                        [
                            'Radacct.username = RadacctLast.username',
                            'Radacct.acctstarttime = RadacctLast.max_acctstarttime',
                        ]
                    );
            },
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
            $accounts = $this->fetchTable('Radius.Accounts')
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
        } catch (MissingConnectionException $connectionError) {
            //Couldn't connect
            $accounts = null;
        }

        $this->set(compact('accounts'));
        $this->set('show_contracts', $this->show_contracts);
    }
}
