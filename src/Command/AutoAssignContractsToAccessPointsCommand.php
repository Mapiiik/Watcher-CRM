<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Contract;
use App\Model\Table\ContractsTable;
use App\NMS\ApiClient as NMSApiClient;
use Cake\Collection\CollectionInterface;
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
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('overwrite', [
            'short' => 'o',
            'help' => 'Reassign access points even for contracts that already have one.',
            'boolean' => true,
        ]);

        $parser->addOption('dry-run', [
            'short' => 'd',
            'help' => 'Do not write anything, only show what would be changed.',
            'boolean' => true,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        $overwrite = (bool)$args->getOption('overwrite');
        $dryRun = (bool)$args->getOption('dry-run');

        $contractsTable = $this->fetchTable(ContractsTable::class);
        $radiusAccountsTable = $this->fetchTable(AccountsTable::class);

        // load contracts
        $query = $contractsTable
            ->find()
            ->contain([
                'IpAddresses',
            ]);

        if (!$overwrite) {
            $query->where([
                'Contracts.access_point_id IS NULL',
            ]);
        }

        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Contract> $contracts */
        $contracts = $query->all();

        foreach ($contracts as $contract) {
            $this->processContract($contract, $radiusAccountsTable, $contractsTable, $io, $dryRun);
        }
    }

    /**
     * Process one contract.
     */
    private function processContract(
        Contract $contract,
        AccountsTable $radiusAccountsTable,
        ContractsTable $contractsTable,
        ConsoleIo $io,
        bool $dryRun,
    ): void {
        // load RADIUS accounts for contract
        /** @var \Cake\Datasource\ResultSetInterface<array-key, \Radius\Model\Entity\Account> $radiusAccounts */
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
            if (!isset($radiusAccount->radacct[0]->nasipaddress)) {
                $io->verbose(sprintf(
                    'No NAS IP for RADIUS account %s of contract %s',
                    $radiusAccount->username,
                    $contract->number,
                ));
                continue;
            }

            $nasIp = $radiusAccount->radacct[0]->nasipaddress;
            $routerosDevices = NMSApiClient::getRouterosDevicesForIp($nasIp);

            if (!$routerosDevices instanceof CollectionInterface) {
                $message = sprintf(
                    'Error when fetching RouterOS devices for NAS IP: %s for contract %s',
                    $nasIp,
                    $contract->number,
                );
                Log::write('error', $message);
                $io->error($message);
                continue;
            }

            foreach ($routerosDevices as $routerosDevice) {
                /** @var array{access_point_id?: string} $routerosDevice */
                if (!isset($routerosDevice['access_point_id'])) {
                    continue;
                }

                $newApId = $routerosDevice['access_point_id'];
                $oldApId = $contract->access_point_id;

                // contract already has this access point, nothing to do
                if ($oldApId === $newApId) {
                    $io->verbose(sprintf(
                        'Contract %s already has access point ID: %s',
                        $contract->number,
                        $newApId,
                    ));

                    return;
                }

                if ($dryRun) {
                    $io->info(sprintf(
                        'DRY-RUN: contract %s | access point ID: %s → %s',
                        $contract->number,
                        $oldApId ?? 'NULL',
                        $newApId,
                    ));

                    return;
                }

                $io->info(sprintf(
                    'Assigning access point ID: %s to contract %s',
                    $newApId,
                    $contract->number,
                ));

                $query = $contractsTable->updateQuery()
                    ->set([
                        'access_point_id' => $newApId,
                    ])
                    ->where([
                        'id' => $contract->id,
                    ]);

                if ($query->execute()->rowCount() === 1) {
                    Log::write('debug', sprintf(
                        'Assigned access point ID: %s to contract %s',
                        $newApId,
                        $contract->number,
                    ));

                    return; // stop processing this contract
                }

                $message = sprintf(
                    'Error when assigning access point ID: %s to contract %s',
                    $newApId,
                    $contract->number,
                );
                Log::write('error', $message);
                $io->error($message);
            }
        }
    }
}
