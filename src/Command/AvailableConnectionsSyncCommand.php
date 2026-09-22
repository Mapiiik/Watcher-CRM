<?php
declare(strict_types=1);

namespace App\Command;

use App\Addresses\Resolver as AddressesResolver;
use App\Model\Entity\AvailableConnection;
use App\Model\Enum\AccessMedium;
use App\Model\Enum\AvailableConnectionOrigin;
use App\Model\Table\AvailableConnectionsTable;
use App\Model\Table\ContractsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Override;
use RuntimeException;

/**
 * Records every address point a contract ever put a connection at as an available connection.
 *
 * Meant to run daily. A line stays in the building when the contract ends, and the record stays
 * when the contract is one day erased, which is the whole point of keeping it apart.
 *
 * Wireless is the exception: the connection is the kit on the roof, so only the active ones are
 * recorded unless asked otherwise. What is already recorded stays either way.
 *
 * Never removes anything. Only raises what it wrote itself, and a record the operator took over,
 * or retired, is left as it is.
 */
class AvailableConnectionsSyncCommand extends Command
{
    /**
     * @inheritDoc
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'available_connections sync';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(
            'Records the address points of all contracts, ended ones too, as available connections.'
            . ' Meant to run daily from cron.',
        );

        $parser->addOption('include-ended-wireless', [
            'short' => 'w',
            'help' => 'Record wireless connections of ended contracts too, not only the active ones.',
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
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int The exit code
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $dryRun = (bool)$args->getOption('dry-run');
        $endedWireless = (bool)$args->getOption('include-ended-wireless');
        $available = $this->fetchTable(AvailableConnectionsTable::class);

        $wanted = $this->wanted($endedWireless);

        /** @var array<string, \App\Model\Entity\AvailableConnection> $existing */
        $existing = [];
        foreach ($available->find()->all() as $record) {
            /** @var \App\Model\Entity\AvailableConnection $record */
            $existing[$record->registryKey() . '|' . $record->access_technology->value] = $record;
        }

        $labels = $this->labels(array_diff_key($wanted, $existing), $io);

        $changed = [];
        foreach ($wanted as $key => $want) {
            $record = $existing[$key] ?? null;

            if ($record === null) {
                $record = $available->newEntity([
                    'address_registry_source' => $want['source'],
                    'address_registry_reference' => $want['reference'],
                    'address_label' => $labels[$want['source'] . '|' . $want['reference']] ?? $want['label'],
                    'gps_x' => $want['gps_x'],
                    'gps_y' => $want['gps_y'],
                    'access_technology' => $want['technology'],
                    'speed_down_max' => $want['speed_down'],
                    'speed_up_max' => $want['speed_up'],
                ]);
                $record->origin = AvailableConnectionOrigin::Contract;
                $record->contract_id = $want['contract_id'];
                $changed[] = $record;

                continue;
            }

            if ($record->retired !== null) {
                continue;
            }

            $record->contract_id ??= $want['contract_id'];

            if ($record->origin === AvailableConnectionOrigin::Contract) {
                $record->speed_down_max = max($record->speed_down_max, $want['speed_down']);
                $record->speed_up_max = max($record->speed_up_max, $want['speed_up']);
            }

            if ($record->isDirty()) {
                $changed[] = $record;
            }
        }

        $new = count(array_filter($changed, fn(AvailableConnection $record): bool => $record->isNew()));
        $io->out(sprintf('%d new, %d updated.', $new, count($changed) - $new));

        if ($dryRun || $changed === []) {
            return static::CODE_SUCCESS;
        }

        // One transaction for the lot: quicker, and the audit log keeps the run together.
        $available->saveManyOrFail($changed);

        return static::CODE_SUCCESS;
    }

    /**
     * What the contracts say should be there, by "source|reference|technology".
     *
     * Every contract that ever had a billing on a profile with a technology, at an installation
     * address the registry knows. The fastest profile a point ever had is what it can carry.
     * A wireless billing counts only while it runs, unless the ended ones are asked for.
     *
     * @param bool $endedWireless Whether wireless billings that have ended count too.
     * @return array<string, array{source: string, reference: string, technology: \App\Model\Enum\AccessTechnology, speed_down: int, speed_up: int, contract_id: string, label: string|null, gps_x: float|null, gps_y: float|null}>
     */
    private function wanted(bool $endedWireless): array
    {
        $today = Date::now();

        $contracts = $this->fetchTable(ContractsTable::class)->find()
            ->contain(['InstallationAddresses', 'Billings' => ['Services' => ['ConnectionProfiles']]])
            ->innerJoinWith('InstallationAddresses')
            ->where([
                'InstallationAddresses.address_registry_source IS NOT' => null,
                'InstallationAddresses.address_registry_reference IS NOT' => null,
            ])
            ->all();

        $wanted = [];
        foreach ($contracts as $contract) {
            /** @var \App\Model\Entity\Contract $contract */
            $address = $contract->installation_address;

            foreach ($contract->billings ?? [] as $billing) {
                $profile = $billing->service?->connection_profile;
                if (
                    $profile?->access_technology === null
                    || $profile->speed_down === null
                    || $profile->speed_up === null
                ) {
                    continue;
                }

                if (
                    !$endedWireless
                    && $profile->access_technology->medium() === AccessMedium::Wireless
                    && !$billing->isActiveOn($today)
                ) {
                    continue;
                }

                $key = $address->address_registry_source . '|' . $address->address_registry_reference
                    . '|' . $profile->access_technology->value;

                $wanted[$key] ??= [
                    'source' => (string)$address->address_registry_source,
                    'reference' => (string)$address->address_registry_reference,
                    'technology' => $profile->access_technology,
                    'speed_down' => 0,
                    'speed_up' => 0,
                    'contract_id' => $contract->id,
                    'label' => $address->full_address ?? null,
                    'gps_x' => $address->gps_x,
                    'gps_y' => $address->gps_y,
                ];
                $wanted[$key]['speed_down'] = max($wanted[$key]['speed_down'], $profile->speed_down);
                $wanted[$key]['speed_up'] = max($wanted[$key]['speed_up'], $profile->speed_up);
            }
        }

        return $wanted;
    }

    /**
     * The registry's wording of the address points about to be recorded, by "source|reference".
     *
     * Asked once for all of them. When the registry cannot be asked, the addresses as they stand
     * on the contracts will do.
     *
     * @param array<string, array{source: string, reference: string}> $points The new ones.
     * @param \Cake\Console\ConsoleIo $io Where to say the registry could not be asked.
     * @return array<string, string>
     */
    private function labels(array $points, ConsoleIo $io): array
    {
        if ($points === []) {
            return [];
        }

        $items = array_map(fn(array $point): AvailableConnection => new AvailableConnection([
            'address_registry_source' => $point['source'],
            'address_registry_reference' => $point['reference'],
        ]), array_values($points));

        try {
            $matches = AddressesResolver::matchMap($items);
        } catch (RuntimeException $e) {
            $io->warning('The address registry could not be asked: ' . $e->getMessage());

            return [];
        }

        return array_filter(array_map(fn($match): ?string => $match->formattedAddress, $matches));
    }
}
