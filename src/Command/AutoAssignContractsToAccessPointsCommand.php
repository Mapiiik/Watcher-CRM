<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\ContractsTable;
use App\NMS\ApiClient as NMSApiClient;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\Query\SelectQuery;
use Override;
use Radius\Model\Table\AccountsTable;

/**
 * AutoAssignContractsToAccessPoints command.
 */
class AutoAssignContractsToAccessPointsCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $contractsTable = $this->fetchTable(ContractsTable::class);
        $radiusAccountsTable = $this->fetchTable(AccountsTable::class);

        $api = new NMSApiClient();

        // load contracts without assigned access point
        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Contract> $unassignedContracts */
        $unassignedContracts = $contractsTable
            ->find()
            ->contain([
                'IpAddresses',
            ])
            ->where([
                'Contracts.access_point_id IS NULL',
            ])
            ->all();

        foreach ($unassignedContracts as $contract) {
            // load RADIUS accounts for contract
            $radiusAccounts = $radiusAccountsTable
                ->find()
                ->where([
                    'Accounts.contract_id' => $contract->id,
                    'Accounts.active' => true,
                ])
                ->orderBy([
                    'Accounts.id' => 'DESC',
                ])
                // for each RADIUS account find lastly opened session
                ->contain(['Radacct' => function (SelectQuery $q) {
                    return $q
                        ->orderBy([
                            'Radacct.acctstarttime' => 'DESC',
                        ])
                        ->limit(1);
                }])
                ->all();

            foreach ($radiusAccounts as $radiusAccount) {
                // try to find RouterOS devices via API from NMS with RADIUS NAS IP address
                if (isset($radiusAccount->radacct[0]->nasipaddress)) {
                    $routerosDevices = $api->getRouterosDevicesForIp($radiusAccount->radacct[0]->nasipaddress);

                    if ($routerosDevices === null) {
                        Log::write(
                            'error',
                            'Error when fetching RouterOS devices for NAS IP: '
                            . $radiusAccount->radacct[0]->nasipaddress
                            . ' for contract ' . $contract->number,
                        );
                        $io->error(
                            'Error when fetching RouterOS devices for NAS IP: '
                            . $radiusAccount->radacct[0]->nasipaddress
                            . ' for contract ' . $contract->number,
                        );
                        continue;
                    }

                    // if some RouterOS device has assigned access point assign same to contract
                    foreach ($routerosDevices as $routerosDevice) {
                        if (isset($routerosDevice['access_point_id'])) {
                            Log::write(
                                'debug',
                                'Assigning access point ID: ' . $routerosDevice['access_point_id']
                                . ' to contract ' . $contract->number,
                            );
                            $io->info(
                                'Assigning access point ID: ' . $routerosDevice['access_point_id']
                                . ' to contract ' . $contract->number,
                            );

                            $query = $contractsTable->updateQuery()
                                ->set([
                                    'access_point_id' => $routerosDevice['access_point_id'],
                                ])
                                ->where([
                                    'id' => $contract->id,
                                ]);

                            if ($query->execute()->rowCount() == 1) {
                                // stop processing of this contract
                                break 2;
                            } else {
                                Log::write(
                                    'error',
                                    'Error when assigning access point ID: ' . $routerosDevice['access_point_id']
                                    . ' to contract ' . $contract->number,
                                );
                                $io->error(
                                    'Error when assigning access point ID: ' . $routerosDevice['access_point_id']
                                    . ' to contract ' . $contract->number,
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
