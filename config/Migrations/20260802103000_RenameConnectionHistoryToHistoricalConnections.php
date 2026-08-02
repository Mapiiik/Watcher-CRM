<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameConnectionHistoryToHistoricalConnections extends BaseMigration
{
    /**
     * Indexes carrying the old name, as `old => new`.
     *
     * Renaming a table in PostgreSQL leaves its indexes and constraints named
     * after the old one. They are internal names nobody types, but leaving them
     * behind is exactly what makes someone doubt which table they are reading a
     * year from now.
     *
     * @var array<string, string>
     */
    private const INDEXES = [
        'connection_history_account_id_first_seen' => 'historical_connections_account_id_first_seen',
        'connection_history_contract_id_first_seen' => 'historical_connections_contract_id_first_seen',
        'connection_history_customer_id_first_seen' => 'historical_connections_customer_id_first_seen',
        'connection_history_interval' => 'historical_connections_interval',
        'connection_history_station_id' => 'historical_connections_station_id',
    ];

    /**
     * Constraints carrying the old name, as `old => new`.
     *
     * @var array<string, string>
     */
    private const CONSTRAINTS = [
        'connection_history_pkey' => 'historical_connections_pkey',
        'connection_history_contract_id_fkey' => 'historical_connections_contract_id_fkey',
        'connection_history_created_by_fkey' => 'historical_connections_created_by_fkey',
        'connection_history_customer_id_fkey' => 'historical_connections_customer_id_fkey',
        'connection_history_modified_by_fkey' => 'historical_connections_modified_by_fkey',
    ];

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('connection_history')
            ->rename('historical_connections')
            ->update();

        $this->renameObjects('historical_connections', self::INDEXES, self::CONSTRAINTS);
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('historical_connections')
            ->rename('connection_history')
            ->update();

        $this->renameObjects(
            'connection_history',
            array_flip(self::INDEXES),
            array_flip(self::CONSTRAINTS),
        );
    }

    /**
     * Rename the indexes and constraints of a table.
     *
     * @param string $table Table they belong to.
     * @param array<string, string> $indexes Indexes as `old => new`.
     * @param array<string, string> $constraints Constraints as `old => new`.
     * @return void
     */
    private function renameObjects(string $table, array $indexes, array $constraints): void
    {
        foreach ($indexes as $from => $to) {
            $this->execute(sprintf('ALTER INDEX IF EXISTS %s RENAME TO %s', $from, $to));
        }

        foreach ($constraints as $from => $to) {
            $this->execute(sprintf('ALTER TABLE %s RENAME CONSTRAINT %s TO %s', $table, $from, $to));
        }
    }
}
