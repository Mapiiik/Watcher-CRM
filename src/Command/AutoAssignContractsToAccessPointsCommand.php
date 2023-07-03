<?php
declare(strict_types=1);

namespace App\Command;

use App\ApiClient;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;

/**
 * AutoAssignContractsToAccessPoints command.
 */
class AutoAssignContractsToAccessPointsCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $contracts_table = $this->fetchTable('Contracts');
        $radius_accounts_table = $this->fetchTable('Radius.Accounts');

        $api = new ApiClient();

        // load contracts without assigned access point
        $unassigned_contracts = $contracts_table
            ->find()
            ->where([
                'Contracts.access_point_id IS NULL',
            ])
            ->contain([
                'Ips',
            ])
            ->all();

        foreach ($unassigned_contracts as $contract) {
            // load RADIUS accounts for contract
            $radius_accounts = $radius_accounts_table
                ->find()
                ->where([
                    'Accounts.contract_id' => $contract->id,
                    'Accounts.active' => true,
                ])
                ->order(['Accounts.id' => 'DESC'])
                // for each RADIUS account find lastly opened session
                ->contain(['Radacct' => function ($q) {
                    return $q->order(['Radacct.acctstarttime' => 'DESC'])->limit(1);
                }])
                ->all();

            foreach ($radius_accounts as $radius_account) {
                // try to find RouterOS devices via API from NMS with RADIUS NAS IP address
                if (isset($radius_account->radacct[0]->nasipaddress)) {
                    $routeros_devices = $api->getRouterosDevicesForIp($radius_account->radacct[0]->nasipaddress);

                    // if some RouterOS device has assigned access point assign same to contract
                    foreach ($routeros_devices as $routeros_device) {
                        if (isset($routeros_device['access_point_id'])) {
                            Log::write(
                                'debug',
                                'Assigning access point ID: ' . $routeros_device['access_point_id']
                                . ' to contract ' . $contract->number
                            );
                            $io->info(
                                'Assigning access point ID: ' . $routeros_device['access_point_id']
                                . ' to contract ' . $contract->number
                            );

                            $query = $contracts_table->updateQuery()
                                ->set([
                                    'access_point_id' => $routeros_device['access_point_id'],
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
                                    'Error when assigning access point ID: ' . $routeros_device['access_point_id']
                                    . ' to contract ' . $contract->number
                                );
                                $io->error(
                                    'Error when assigning access point ID: ' . $routeros_device['access_point_id']
                                    . ' to contract ' . $contract->number
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
