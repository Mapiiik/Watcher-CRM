<?php
declare(strict_types=1);

namespace Radius\View\Cell;

use Cake\Database\Exception\MissingConnectionException;
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
     * @var array<string>
     */
    protected $_validCellOptions = ['show_contracts'];

    /**
     * Show contracts
     *
     * @var bool
     */
    protected $show_contracts = true;

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
    public function display(array $conditions = [])
    {
        try {
            //Try to load RADIUS accounts
            $accounts = $this->fetchTable('Radius.Accounts')->find('all', [
                'conditions' => $conditions,
                'contain' => [
                    'Contracts',
                    'Radreply',
                    'Radusergroup',
                    'Radacct' => ['sort' => ['Radacct.acctstarttime' => 'DESC']],
                ],
                'order' => [
                    'Accounts.contract_id',
                    'Accounts.active',
                    'Accounts.username',
                ],
            ]);
        } catch (MissingConnectionException $connectionError) {
            //Couldn't connect
            $accounts = null;
        }

        $this->set(compact('accounts'));
        $this->set('show_contracts', $this->show_contracts);
    }
}
