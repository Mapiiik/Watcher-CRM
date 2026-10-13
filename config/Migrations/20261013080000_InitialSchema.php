<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;
use Migrations\Migration\IrreversibleMigrationException;

/**
 * Where 5.x begins: the schema the migrations of 4.x built, in one step.
 *
 * An empty database is given all of it. One that 4.x brought to its last migration has it already,
 * but for a few names some installations went their own way with, and those are put right. Then it
 * only forgets the steps that led there. Any other is turned away, as those steps are no longer here
 * to take it the rest of the way.
 */
class InitialSchema extends BaseMigration
{
    /**
     * The last migration of 4.x.
     *
     * @var int
     */
    private const LAST_OF_4X = 20261013070000;

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('phinxlog')) {
            throw new RuntimeException(
                'The database keeps the record of its migrations in the phinxlog tables of 4.x.'
                . ' Move it with `bin/cake migrations upgrade --drop-tables`, then migrate again.',
            );
        }

        $last = $this->fetchRow(sprintf(
            'SELECT 1 FROM cake_migrations WHERE plugin IS NULL AND version = %d',
            self::LAST_OF_4X,
        ));
        if ($last !== false) {
            $this->alignNames();

            // the accounts were created by CakeDC/Users once, and are the application's own now
            $this->execute(sprintf(
                "DELETE FROM cake_migrations WHERE (plugin IS NULL AND version < %d) OR plugin = 'CakeDC/Users'",
                $this->getVersion(),
            ));

            return;
        }

        if ($this->hasTable('users')) {
            throw new RuntimeException(
                'The database has not been through the last migration of 4.x.'
                . ' Update the application to the last 4.x release and migrate there first.',
            );
        }

        $this->createTables();

        // every contract is in some state, so there is one to begin with
        $this->table('contract_states')
            ->insert(['name' => 'Default', 'active_services' => true, 'billed' => true, 'blocked' => false])
            ->save();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        throw new IrreversibleMigrationException('What 5.x begins with is not taken apart.');
    }

    /**
     * What some databases of 4.x named their own way, named as here. The migrations of 4.x never
     * compared names, so nothing else would bring them in line.
     *
     * @return void
     */
    private function alignNames(): void
    {
        $this->execute('ALTER INDEX IF EXISTS index_number RENAME TO contracts_number');
        $this->execute('ALTER INDEX IF EXISTS login_index RENAME TO logins_login');

        // written while proposals were still called rounds
        $this->execute(
            'COMMENT ON COLUMN contract_proposals.customer_proposal_id'
            . " IS 'The proposal these papers go out in'",
        );
        $this->execute(
            'COMMENT ON COLUMN customer_proposals.purpose'
            . " IS 'What the proposal is for, as App\\Model\\Enum\\CustomerProposalPurpose,"
            . " empty when it only holds its contracts papers'",
        );
    }

    /**
     * The tables as 4.x left them.
     *
     * @return void
     */
    private function createTables(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->execute('CREATE EXTENSION IF NOT EXISTS unaccent');

        // what the customer search folds case and accents with; dropped first because the test
        // runner starts over by dropping the tables, and a configuration is not one
        $this->execute('DROP TEXT SEARCH CONFIGURATION IF EXISTS simple_unaccent');
        $this->execute('CREATE TEXT SEARCH CONFIGURATION simple_unaccent (COPY = simple)');
        $this->execute(
            'ALTER TEXT SEARCH CONFIGURATION simple_unaccent'
            . ' ALTER MAPPING FOR hword, hword_part, word WITH unaccent, simple',
        );

        $this->table('access_credentials', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('contract_id', 'uuid', ['null' => true])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('username', 'string', ['null' => true])
            ->addColumn('password', 'string', ['null' => true])
            ->addColumn('ip_address', 'inet', ['null' => true])
            ->addColumn('port', 'integer', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['customer_id'], ['name' => 'access_credentials_customer_id'])
            ->addIndex(['contract_id'], ['name' => 'access_credentials_contract_id'])
            ->create();

        $this->table('accounting_profiles', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('vat_rate', 'float', ['default' => 0, 'null' => false])
            ->addColumn('reverse_charge', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('accounting_assignment_code', 'string', ['null' => true])
            ->addColumn('bank_account_code', 'string', ['null' => true])
            ->addColumn('activity_code', 'string', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('invoice_with_items', 'boolean', ['default' => false, 'null' => false])
            ->create();

        $this->table('addresses', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('type', 'integer', ['null' => false])
            ->addColumn('title', 'string', ['null' => true])
            ->addColumn('first_name', 'string', ['null' => true])
            ->addColumn('last_name', 'string', ['null' => true])
            ->addColumn('suffix', 'string', ['null' => true])
            ->addColumn('company', 'string', ['null' => true])
            ->addColumn('street', 'string', ['null' => true])
            ->addColumn('number', 'string', ['null' => true])
            ->addColumn('city', 'string', ['null' => true])
            ->addColumn('zip', 'string', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('address_registry_reference', 'string', ['null' => true])
            ->addColumn('gps_x', 'float', ['null' => true])
            ->addColumn('gps_y', 'float', ['null' => true])
            ->addColumn('number_type', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('manual_coordinate_setting', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('country_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('address_registry_source', 'string', ['limit' => 2, 'null' => true])
            ->addColumn('entrance', 'string', ['null' => true])
            ->addColumn('unit', 'string', ['null' => true])
            ->addIndex(['address_registry_source', 'address_registry_reference'], [
                'name' => 'addresses_address_registry_source_address_registry_reference',
            ])
            ->addIndex(['customer_id'], ['name' => 'addresses_customer_id'])
            ->create();

        $this->table('audit_logs', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('transaction_key', 'uuid', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 7, 'null' => false])
            ->addColumn('primary_key', 'json', ['null' => true])
            ->addColumn('display_value', 'string', ['null' => true])
            ->addColumn('source', 'string', ['null' => false])
            ->addColumn('parent_source', 'string', ['null' => true])
            ->addColumn('user_display', 'string', ['null' => true])
            ->addColumn('original', 'json', ['null' => true])
            ->addColumn('changed', 'json', ['null' => true])
            ->addColumn('meta', 'json', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('user_id', 'uuid', ['null' => true])
            ->addIndex(['transaction_key'], ['name' => 'audit_logs_transaction'])
            ->addIndex(['type'], ['name' => 'audit_logs_type'])
            ->addIndex(['primary_key'], ['name' => 'audit_logs_primary_key'])
            ->addIndex(['display_value'], ['name' => 'audit_logs_display_value'])
            ->addIndex(['source'], ['name' => 'audit_logs_source'])
            ->addIndex(['parent_source'], ['name' => 'audit_logs_parent_source'])
            ->addIndex(['user_display'], ['name' => 'audit_logs_username'])
            ->addIndex(['created'], ['name' => 'audit_logs_created'])
            ->addIndex(['user_id'], ['name' => 'audit_logs_user_id'])
            ->create();

        $this->table('available_connections', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('address_registry_source', 'string', [
                'limit' => 2,
                'null' => false,
                'comment' => 'Which national address registry, as on addresses',
            ])
            ->addColumn('address_registry_reference', 'string', [
                'null' => false,
                'comment' => 'The address point in that registry',
            ])
            ->addColumn('address_label', 'string', [
                'null' => true,
                'comment' => 'The address as the registry wrote it when the point was recorded',
            ])
            ->addColumn('gps_x', 'float', ['null' => true])
            ->addColumn('gps_y', 'float', ['null' => true])
            ->addColumn('access_technology', 'string', [
                'limit' => 32,
                'null' => false,
                'comment' => 'As App\\Model\\Enum\\AccessTechnology',
            ])
            ->addColumn('speed_down_max', 'biginteger', [
                'null' => false,
                'comment' => 'The fastest download the connection can carry, in kbps',
            ])
            ->addColumn('speed_up_max', 'biginteger', [
                'null' => false,
                'comment' => 'The fastest upload the connection can carry, in kbps',
            ])
            ->addColumn('access_point_id', 'uuid', [
                'null' => true,
                'comment' => 'The access point in NMS it is served from',
            ])
            ->addColumn('origin', 'string', [
                'default' => 'manual',
                'limit' => 16,
                'null' => false,
                'comment' => 'As App\\Model\\Enum\\AvailableConnectionOrigin',
            ])
            ->addColumn('contract_id', 'uuid', [
                'null' => true,
                'comment' => 'The contract that put the connection there, while it exists',
            ])
            ->addColumn('retired', 'date', [
                'null' => true,
                'comment' => 'Since when the connection is no longer there to be had',
            ])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['address_registry_source', 'address_registry_reference', 'access_technology'], [
                'name' => 'available_connections_point_technology',
                'unique' => true,
            ])
            ->addIndex(['contract_id'], ['name' => 'available_connections_contract_id'])
            ->create();

        $this->table('billings', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('text', 'string', ['null' => true])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('billing_from', 'date', ['null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('billing_until', 'date', ['null' => true])
            ->addColumn('separate_invoice', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('quantity', 'integer', ['default' => 1, 'null' => false])
            ->addColumn('fixed_discount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('percentage_discount', 'integer', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('service_id', 'uuid', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['contract_id'], ['name' => 'billings_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'billings_customer_id'])
            ->create();

        $this->table('borrowed_equipments', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('serial_number', 'string', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['default' => Literal::from('now()'), 'null' => false])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('borrowed_from', 'date', ['null' => true])
            ->addColumn('borrowed_until', 'date', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('equipment_type_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['contract_id'], ['name' => 'borrowed_equipments_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'borrowed_equipments_customer_id'])
            ->create();

        $this->table('commissions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->create();

        $this->table('connection_profiles', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('radius_group', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('fup_limit', 'biginteger', ['null' => true])
            ->addColumn('data_limit', 'biginteger', ['null' => true])
            ->addColumn('overlimit_fragment', 'integer', ['null' => true])
            ->addColumn('overlimit_cost', 'integer', ['null' => true])
            ->addColumn('speed_up', 'biginteger', ['null' => true])
            ->addColumn('speed_down', 'biginteger', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('speed_down_common', 'biginteger', [
                'null' => true,
                'comment' => 'Declared commonly available download speed in kbps, empty derives from speed_down',
            ])
            ->addColumn('speed_up_common', 'biginteger', [
                'null' => true,
                'comment' => 'Declared commonly available upload speed in kbps, empty derives from speed_up',
            ])
            ->addColumn('speed_down_minimum', 'biginteger', [
                'null' => true,
                'comment' => 'Declared minimum download speed in kbps, empty derives from speed_down',
            ])
            ->addColumn('speed_up_minimum', 'biginteger', [
                'null' => true,
                'comment' => 'Declared minimum upload speed in kbps, empty derives from speed_up',
            ])
            ->addColumn('access_technology', 'string', ['limit' => 32, 'null' => true])
            ->create();

        $this->table('contract_proposals', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('contract_version_id', 'uuid', [
                'null' => true,
                'comment' => 'The version the paper is about, empty while it is still to be created',
            ])
            ->addColumn('terminates_contract_version_id', 'uuid', [
                'null' => true,
                'comment' => 'An earlier version of the same contract that this proposal terminates',
            ])
            ->addColumn('terminated_contract_number', 'string', [
                'null' => true,
                'comment' => 'The number of the terminated contract, as it stood on the paper',
            ])
            ->addColumn('effective_from', 'date', [
                'null' => false,
                'comment' => 'The day the proposal takes effect; on an amendment, its effective date',
            ])
            ->addColumn('snapshot', 'json', [
                'null' => false,
                'comment' => 'How everything stood when the proposal was drawn up; documents print from this',
            ])
            ->addColumn('snapshot_taken', 'timestamptimezone', [
                'null' => false,
                'comment' => 'When the snapshot was last taken',
            ])
            ->addColumn('changes', 'json', [
                'null' => false,
                'comment' => 'What is to happen once the proposal is signed',
            ])
            ->addColumn('confirmations', 'json', [
                'default' => '{}',
                'null' => false,
                'comment' => 'What the operator confirmed against the readiness checks',
            ])
            ->addColumn('applied', 'timestamptimezone', [
                'null' => true,
                'comment' => 'When the changes were carried over into the live records',
            ])
            ->addColumn('applied_by', 'uuid', ['null' => true])
            ->addColumn('revoked', 'timestamptimezone', ['null' => true])
            ->addColumn('revoked_by', 'uuid', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('purpose', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'What the proposal is for, as App\\Model\\Enum\\ProposalPurpose',
            ])
            ->addColumn('customer_proposal_id', 'uuid', [
                'null' => false,
                'comment' => 'The proposal these papers go out in',
            ])
            ->addColumn('left_out', 'json', [
                'default' => '{}',
                'null' => false,
                'comment' => 'The lines applying the changes could not write, and why',
            ])
            ->addColumn('left_out_settled', 'timestamptimezone', [
                'null' => true,
                'comment' => 'When somebody saw to what applying the changes left out',
            ])
            ->addColumn('left_out_settled_by', 'uuid', ['null' => true, 'comment' => 'Who saw to it'])
            ->addIndex(['contract_id'], ['name' => 'contract_version_proposals_contract_id'])
            ->addIndex(['contract_id'], [
                'name' => 'contract_proposals_open',
                'where' => 'applied IS NULL AND revoked IS NULL',
            ])
            ->addIndex(['contract_version_id'], ['name' => 'contract_version_proposals_contract_version_id'])
            ->addIndex(['contract_version_id', 'customer_proposal_id'], [
                'name' => 'contract_proposals_contract_version_id_customer_proposal_id',
            ])
            ->addIndex(['customer_proposal_id'], ['name' => 'contract_proposals_customer_proposal_id'])
            ->create();

        $this->table('contract_states', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('color', 'string', ['default' => '#ffffff', 'limit' => 7, 'null' => false])
            ->addColumn('active_services', 'boolean', ['null' => false])
            ->addColumn('billed', 'boolean', ['null' => false])
            ->addColumn('blocked', 'boolean', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('usable_for_new_contract', 'boolean', [
                'default' => true,
                'null' => false,
                'comment' => 'State can be selected when creating a new contract',
            ])
            ->addColumn('requires_open_task_type_id', 'uuid', [
                'null' => true,
                'comment' => 'Requires an open task of given task_type',
            ])
            ->addColumn('requires_no_open_tasks', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'No open tasks may exist for the contract',
            ])
            ->addColumn('requires_no_active_billings', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'No billing active at current time is allowed',
            ])
            ->addColumn('requires_no_future_billings', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'No billing starting in the future is allowed',
            ])
            ->addColumn('requires_no_assigned_ip_addresses_or_networks', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'All IP addresses and networks must be unassigned',
            ])
            ->addColumn('requires_no_active_radius_accounts', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'No active RADIUS accounts may exist',
            ])
            ->addColumn('requires_no_borrowed_equipments', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'No borrowed equipments may be active',
            ])
            ->addColumn('requires_installation_date', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Installation date must be set',
            ])
            ->addColumn('requires_uninstallation_date', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Uninstallation date must be set',
            ])
            ->addColumn('requires_termination_date', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Termination date must be set',
            ])
            ->addColumn('requires_contract_version', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'At least one contract version must exist',
            ])
            ->addColumn('requires_active_contract_version', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'An active contract version must exist',
            ])
            ->addColumn('requires_active_or_future_contract_version', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'An active or future contract version must exist',
            ])
            ->addColumn('requires_no_active_or_future_contract_versions', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'No active or future contract versions may exist',
            ])
            ->addColumn('requires_no_active_obligations', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'No active contractual obligations may exist',
            ])
            ->addColumn('show_on_dashboard', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('dashboard_roles', 'json', ['null' => true])
            ->addColumn('requires_versions_matching_termination', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'The last contract version must end on the termination date',
            ])
            ->addColumn('requires_billings_matching_termination', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'The last billing must end on the termination date',
            ])
            ->addColumn('requires_equipments_matching_uninstallation', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'The last borrowed equipment must be returned on the uninstallation date',
            ])
            ->addColumn('requires_open_customer_task_type_id', 'uuid', [
                'null' => true,
                'comment' => 'Requires an open task of given task_type on the customer of the contract',
            ])
            ->create();

        $this->table('contract_versions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('valid_from', 'date', ['null' => false])
            ->addColumn('valid_until', 'date', ['null' => true])
            ->addColumn('obligation_until', 'date', ['null' => true])
            ->addColumn('conclusion_date', 'date', ['null' => true])
            ->addColumn('number_of_amendments', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('obligations_settled', 'boolean', ['default' => false, 'null' => false])
            ->addIndex(['contract_id'], ['name' => 'contract_versions_contract_id'])
            ->create();

        $this->table('contracts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('nid', 'integer', ['identity' => true, 'null' => false])
            ->addColumn('number', 'string', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['default' => Literal::from('now()'), 'null' => false])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('vip', 'boolean', ['null' => true])
            ->addColumn('installation_date', 'date', ['null' => true])
            ->addColumn('access_description', 'text', ['null' => true])
            ->addColumn('activation_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('activation_fee_with_obligation', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('access_point_id', 'uuid', ['null' => true])
            ->addColumn('contract_state_id', 'uuid', ['null' => false])
            ->addColumn('uninstallation_date', 'date', ['null' => true])
            ->addColumn('termination_date', 'date', ['null' => true])
            ->addColumn('subscriber_verification_code', 'string', ['null' => true])
            ->addColumn('installation_address_id', 'uuid', ['null' => true])
            ->addColumn('commission_id', 'uuid', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('installation_technician_id', 'uuid', ['null' => true])
            ->addColumn('uninstallation_technician_id', 'uuid', ['null' => true])
            ->addColumn('service_type_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('individual_terms', 'text', [
                'null' => true,
                'comment' => 'What was agreed with this customer beyond the standard terms; printed on the contract',
            ])
            ->addColumn('minimum_connection_price', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true,
                'comment' => 'The monthly price the connection may not be lowered below; empty for no limit',
            ])
            ->addIndex(['nid'], ['name' => 'contracts_nid', 'unique' => true])
            ->addIndex(['number'], ['name' => 'contracts_number', 'unique' => true])
            ->addIndex(['customer_id', 'contract_state_id'], ['name' => 'contracts_customer_id_contract_state_id'])
            ->addIndex(['customer_id'], ['name' => 'contracts_customer_id'])
            ->addIndex(['customer_id', 'service_type_id'], ['name' => 'contracts_customer_id_service_type_id'])
            ->create();

        $this->table('countries', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('code', 'string', ['limit' => 2, 'null' => true])
            ->create();

        $this->table('customer_labels', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['default' => Literal::from('now()'), 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('label_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => true])
            ->addIndex(['customer_id', 'label_id'], ['name' => 'customer_labels_customer_id_label_id'])
            ->create();

        $this->table('customer_messages', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('type', 'smallinteger', ['null' => false, 'comment' => '[enum] email:10,sms:20'])
            ->addColumn('direction', 'smallinteger', ['null' => false, 'comment' => '[enum] outgoing:10,incoming:20'])
            ->addColumn('recipients', 'json', ['null' => false])
            ->addColumn('subject', 'string', ['null' => false])
            ->addColumn('body', 'text', ['null' => false])
            ->addColumn('body_format', 'smallinteger', ['null' => false, 'comment' => '[enum] plaintext:0,html:10'])
            ->addColumn('delivery_status', 'smallinteger', [
                'null' => false,
                'comment' => '[enum] pending:0,sent:10,delivered:20,failed:30',
            ])
            ->addColumn('processed', 'timestamptimezone', ['null' => true])
            ->addColumn('identifier', 'string', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => false])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('attachments', 'json', ['null' => true])
            ->addIndex(['customer_id'], ['name' => 'customer_messages_customer_id'])
            ->addIndex(['customer_id', 'created'], ['name' => 'customer_messages_customer_id_created'])
            ->create();

        $this->table('customer_proposals', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('purpose', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'What the proposal is for, as App\\Model\\Enum\\CustomerProposalPurpose, empty when it'
                    . ' only holds its contracts papers',
            ])
            ->addColumn('effective_from', 'date', ['null' => false, 'comment' => 'The day the paper relates to'])
            ->addColumn('sent_date', 'date', [
                'null' => true,
                'comment' => 'When the paper was last sent to the customer',
            ])
            ->addColumn('delivery_type', 'integer', [
                'null' => true,
                'comment' => 'How it was sent, as App\\Model\\Enum\\DocumentsDeliveryType',
            ])
            ->addColumn('conclusion_date', 'date', [
                'null' => true,
                'comment' => 'The day the customer signed or otherwise agreed to it',
            ])
            ->addColumn('revoked', 'timestamptimezone', ['null' => true])
            ->addColumn('revoked_by', 'uuid', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['customer_id'], ['name' => 'customer_proposals_customer_id'])
            ->addIndex(['customer_id', 'created'], ['name' => 'customer_proposals_customer_id_created'])
            ->addIndex(['customer_id'], [
                'name' => 'customer_proposals_open',
                'where' => 'conclusion_date IS NULL AND revoked IS NULL',
            ])
            ->create();

        $this->table('customers', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('nid', 'integer', ['identity' => true, 'null' => false])
            ->addColumn('dealer', 'smallinteger', ['default' => 0, 'null' => false])
            ->addColumn('title', 'string', ['null' => true])
            ->addColumn('first_name', 'string', ['null' => true])
            ->addColumn('last_name', 'string', ['null' => true])
            ->addColumn('suffix', 'string', ['null' => true])
            ->addColumn('company', 'string', ['null' => true])
            ->addColumn('bank_name', 'string', ['null' => true])
            ->addColumn('bank_account', 'string', ['null' => true])
            ->addColumn('bank_code', 'string', ['limit' => 4, 'null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('identity_number', 'string', ['limit' => 12, 'null' => true])
            ->addColumn('vat_number', 'string', ['limit' => 15, 'null' => true])
            ->addColumn('www', 'string', ['null' => true])
            ->addColumn('internal_note', 'text', ['null' => true])
            ->addColumn('invoice_delivery_type', 'smallinteger', ['default' => 1, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('identity_card_number', 'string', ['limit' => 12, 'null' => true])
            ->addColumn('date_of_birth', 'date', ['null' => true])
            ->addColumn('agree_gdpr', 'boolean', ['null' => true])
            ->addColumn('agree_mailing_outages', 'boolean', ['null' => true])
            ->addColumn('agree_mailing_commercial', 'boolean', ['null' => true])
            ->addColumn('agree_mailing_billing', 'boolean', ['null' => true])
            ->addColumn('accounting_profile_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('individual_maturity_period', 'integer', ['null' => true])
            ->addColumn('sync_to_accounting', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['nid'], ['name' => 'customers_nid', 'unique' => true])
            ->create();

        $this->table('dealer_commissions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('fixed', 'float', ['null' => true])
            ->addColumn('percentage', 'float', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('commission_id', 'uuid', ['null' => false])
            ->addColumn('dealer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->create();

        $this->table('documentation_types', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('position', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('currently_offered', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('date_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('customer_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('contract_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['position'], ['name' => 'documentation_types_position'])
            ->create();

        $this->table('documentations', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => true])
            ->addColumn('documentation_type_id', 'uuid', ['null' => false])
            ->addColumn('happened_on', 'date', ['null' => true])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['customer_id', 'happened_on'], ['name' => 'documentations_customer_id_happened_on'])
            ->addIndex(['contract_id', 'happened_on'], ['name' => 'documentations_contract_id_happened_on'])
            ->addIndex(['documentation_type_id', 'happened_on'], [
                'name' => 'documentations_documentation_type_id_happened_on',
            ])
            ->create();

        $this->table('emails', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('email', 'string', ['null' => false])
            ->addColumn('use_for_billing', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('use_for_outages', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('use_for_commercial', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['customer_id'], ['name' => 'emails_customer_id'])
            ->create();

        $this->table('equipment_types', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('price_with_obligation', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->create();

        $this->table('failed_password_attempts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->create();

        $this->table('fulltext_search_customers', ['id' => false, 'primary_key' => ['customer_id']])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('modified', 'timestamptimezone', ['default' => Literal::from('now()'), 'null' => false])
            ->create();
        // a type the table builder does not know
        $this->execute('ALTER TABLE fulltext_search_customers ADD COLUMN document tsvector NOT NULL');
        $this->table('fulltext_search_customers')
            ->addIndex(['document'], ['name' => 'fulltext_search_customers_document', 'type' => 'gin'])
            ->update();

        $this->table('historical_connections', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('source', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('source_reference', 'string', ['null' => false])
            ->addColumn('account_id', 'uuid', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => true])
            ->addColumn('station_id', 'string', ['null' => true])
            ->addColumn('called_station_id', 'string', ['null' => true])
            ->addColumn('nas_ip_address', 'string', ['limit' => 39, 'null' => true])
            ->addColumn('nas_port_id', 'string', ['null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 39, 'null' => true])
            ->addColumn('ipv6_prefix', 'string', ['limit' => 43, 'null' => true])
            ->addColumn('access_point_id', 'uuid', ['null' => true])
            ->addColumn('access_point_name', 'string', ['null' => true])
            ->addColumn('routeros_device_id', 'uuid', ['null' => true])
            ->addColumn('routeros_device_name', 'string', ['null' => true])
            ->addColumn('first_seen', 'timestamptimezone', ['null' => false])
            ->addColumn('first_seen_source', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('last_seen', 'timestamptimezone', ['null' => false])
            ->addColumn('enriched', 'timestamptimezone', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['source', 'source_reference', 'first_seen'], [
                'name' => 'historical_connections_interval',
                'unique' => true,
            ])
            ->addIndex(['account_id', 'first_seen'], ['name' => 'historical_connections_account_id_first_seen'])
            ->addIndex(['customer_id', 'first_seen'], ['name' => 'historical_connections_customer_id_first_seen'])
            ->addIndex(['contract_id', 'first_seen'], ['name' => 'historical_connections_contract_id_first_seen'])
            ->addIndex(['station_id'], ['name' => 'historical_connections_station_id'])
            ->create();

        $this->table('ip_addresses', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('ip_address', 'inet', ['null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('type_of_use', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['ip_address'], ['name' => 'ips_ip', 'unique' => true])
            ->addIndex(['contract_id'], ['name' => 'ip_addresses_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'ip_addresses_customer_id'])
            ->create();

        $this->table('ip_networks', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('ip_network', 'cidr', ['null' => false])
            ->addColumn('type_of_use', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => false])
            ->addColumn('modified', 'timestamptimezone', ['null' => false])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['ip_network'], ['name' => 'ip_networks_ip_network', 'unique' => true])
            ->addIndex(['contract_id'], ['name' => 'ip_networks_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'ip_networks_customer_id'])
            ->create();

        $this->table('labels', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('caption', 'string', ['null' => true])
            ->addColumn('color', 'string', ['default' => '#ffffff', 'limit' => 7, 'null' => false])
            ->addColumn('validity', 'integer', ['null' => true])
            ->addColumn('dynamic', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('dynamic_sql', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('show_on_dashboard', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('dashboard_roles', 'json', ['null' => true])
            ->create();

        $this->table('logins', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('login', 'string', ['null' => false])
            ->addColumn('password', 'string', ['null' => false])
            ->addColumn('rights', 'smallinteger', ['default' => 1, 'null' => false])
            ->addColumn('locked', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('last_granted', 'timestamptimezone', ['null' => true])
            ->addColumn('last_granted_ip', 'inet', ['null' => true])
            ->addColumn('last_denied', 'timestamptimezone', ['null' => true])
            ->addColumn('last_denied_ip', 'inet', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['login'], ['name' => 'logins_login', 'unique' => true])
            ->addIndex(['customer_id'], ['name' => 'logins_customer_id'])
            ->create();

        $this->table('phones', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('phone', 'string', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('use_for_billing', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('use_for_outages', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('use_for_commercial', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['customer_id'], ['name' => 'phones_customer_id'])
            ->create();

        $this->table('removed_ip_addresses', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('removed', 'timestamptimezone', ['null' => false])
            ->addColumn('ip_address', 'inet', ['null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('type_of_use', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('removed_by', 'uuid', ['null' => true])
            ->addIndex(['contract_id'], ['name' => 'removed_ip_addresses_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'removed_ip_addresses_customer_id'])
            ->create();

        $this->table('removed_ip_networks', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('ip_network', 'cidr', ['null' => false])
            ->addColumn('type_of_use', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('removed', 'timestamptimezone', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('removed_by', 'uuid', ['null' => true])
            ->addIndex(['contract_id'], ['name' => 'removed_ip_networks_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'removed_ip_networks_customer_id'])
            ->create();

        $this->table('service_overrides', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('service_id', 'uuid', ['null' => false])
            ->addColumn('valid_from', 'date', ['null' => false])
            ->addColumn('valid_until', 'date', ['null' => false])
            ->addColumn('reason', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('revoked', 'timestamptimezone', ['null' => true])
            ->addColumn('revoked_by', 'uuid', ['null' => true])
            ->addIndex(['contract_id'], ['name' => 'service_overrides_contract_id'])
            ->create();

        $this->table('service_types', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('contract_number_format', 'string', ['null' => true])
            ->addColumn('activation_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('activation_fee_with_obligation', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('separate_invoice', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('invoice_with_items', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('invoice_text', 'string', ['null' => true])
            ->addColumn('installation_address_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('normally_with_borrowed_equipment', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('access_point_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('have_contract_versions', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('have_equipments', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('have_ip_addresses', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('have_radius_accounts', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('subscriber_verification_code_format', 'string', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('assign_ip_addresses_from_behind', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('service_terms', 'text', [
                'null' => true,
                'comment' => 'What holds for every contract of this kind of service; printed on the contract',
            ])
            ->create();

        $this->table('services', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['default' => Literal::from('now()'), 'null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('currently_offered', 'boolean', [
                'default' => true,
                'null' => false,
                'comment' => 'Whether the service is still on offer; what is not is left out of the lists',
            ])
            ->addColumn('connection_profile_id', 'uuid', ['null' => true])
            ->addColumn('service_type_id', 'uuid', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('accounting_product_code', 'string', ['null' => true])
            ->addColumn('criticality_level', 'smallinteger', [
                'default' => 10,
                'null' => false,
                'comment' => '[enum] normal:10,important:20,critical:30',
            ])
            ->create();

        $this->table('sessions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 40, 'null' => false])
            ->addColumn('data', 'binary', ['null' => true])
            ->addColumn('expires', 'integer', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->create();

        $this->table('social_accounts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('provider', 'string', ['null' => false])
            ->addColumn('username', 'string', ['null' => true])
            ->addColumn('reference', 'string', ['null' => false])
            ->addColumn('avatar', 'text', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('link', 'string', ['null' => false])
            ->addColumn('token', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('token_secret', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('token_expires', 'timestamptimezone', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('data', 'text', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => false])
            ->addColumn('modified', 'timestamptimezone', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->create();

        $this->table('sold_equipments', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('serial_number', 'string', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['default' => Literal::from('now()'), 'null' => false])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('date_of_sale', 'date', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => false])
            ->addColumn('customer_id', 'uuid', ['null' => false])
            ->addColumn('equipment_type_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['contract_id'], ['name' => 'sold_equipments_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'sold_equipments_customer_id'])
            ->create();

        $this->table('task_collaborators', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('task_id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['task_id', 'user_id'], ['name' => 'task_collaborators_task_id_user_id', 'unique' => true])
            ->addIndex(['user_id'], ['name' => 'task_collaborators_user_id'])
            ->create();

        $this->table('task_states', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'text', ['null' => true])
            ->addColumn('color', 'string', ['default' => '#ffffff', 'limit' => 7, 'null' => false])
            ->addColumn('completed', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('priority', 'integer', ['default' => 0, 'null' => false])
            ->create();

        $this->table('task_types', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('name', 'text', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('customer_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('contract_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('access_point_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('report_on_completion', 'boolean', ['default' => false, 'null' => false])
            ->create();

        $this->table('tasks', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('nid', 'integer', ['identity' => true, 'null' => false])
            ->addColumn('subject', 'string', ['null' => true])
            ->addColumn('text', 'text', ['null' => true])
            ->addColumn('priority', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('email', 'string', ['null' => true])
            ->addColumn('phone', 'string', ['null' => true])
            ->addColumn('finish_date', 'date', ['null' => true])
            ->addColumn('start_date', 'date', ['null' => true])
            ->addColumn('estimated_date', 'date', ['null' => true])
            ->addColumn('critical_date', 'date', ['null' => true])
            ->addColumn('access_point_id', 'uuid', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addColumn('dealer_id', 'uuid', ['null' => true])
            ->addColumn('task_state_id', 'uuid', ['null' => false])
            ->addColumn('task_type_id', 'uuid', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('user_id', 'uuid', ['null' => true])
            ->addIndex(['nid'], ['name' => 'tasks_nid', 'unique' => true])
            ->addIndex(['access_point_id'], ['name' => 'tasks_access_point_id'])
            ->addIndex(['contract_id'], ['name' => 'tasks_contract_id'])
            ->addIndex(['customer_id'], ['name' => 'tasks_customer_id'])
            ->addIndex(['task_state_id'], ['name' => 'tasks_task_state_id'])
            ->addIndex(['task_type_id'], ['name' => 'tasks_task_type_id'])
            ->addIndex(['user_id'], ['name' => 'tasks_user_id'])
            ->create();

        $this->table('users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('username', 'string', ['null' => false])
            ->addColumn('email', 'string', ['null' => true])
            ->addColumn('password', 'string', ['null' => false])
            ->addColumn('first_name', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('last_name', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('token', 'string', ['null' => true])
            ->addColumn('token_expires', 'timestamptimezone', ['null' => true])
            ->addColumn('api_token', 'string', ['null' => true])
            ->addColumn('activation_date', 'timestamptimezone', ['null' => true])
            ->addColumn('tos_date', 'timestamptimezone', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('is_superuser', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('role', 'string', ['default' => 'user', 'null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => false])
            ->addColumn('modified', 'timestamptimezone', ['null' => false])
            ->addColumn('secret', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('secret_verified', 'boolean', ['null' => true])
            ->addColumn('additional_data', 'text', ['null' => true])
            ->addColumn('last_login', 'timestamptimezone', ['null' => true])
            ->addColumn('lockout_time', 'timestamptimezone', ['null' => true])
            ->addColumn('login_token', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('login_token_date', 'timestamptimezone', ['null' => true])
            ->addColumn('token_send_requested', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('nid', 'integer', ['identity' => true, 'null' => false])
            ->addColumn('user_settings', 'json', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addColumn('holds_tasks', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['nid'], ['name' => 'users_nid', 'unique' => true])
            ->addIndex(['login_token'], ['name' => 'users_login_token'])
            ->create();

        $this->table('access_credentials')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'access_credentials_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'access_credentials_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'access_credentials_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'access_credentials_modified_by_fkey'])
            ->update();

        $this->table('accounting_profiles')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'tax_rates_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'tax_rates_modified_by_fkey'])
            ->update();

        $this->table('addresses')
            ->addForeignKey('country_id', 'countries', 'id', ['constraint' => 'addresses_country_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'addresses_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'addresses_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'addresses_modified_by_fkey'])
            ->update();

        $this->table('available_connections')
            ->addForeignKey('contract_id', 'contracts', 'id', [
                'delete' => 'SET_NULL',
                'constraint' => 'available_connections_contract_id_fkey',
            ])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'available_connections_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'available_connections_modified_by_fkey'])
            ->update();

        $this->table('billings')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'billings_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'billings_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'billings_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'billings_modified_by_fkey'])
            ->addForeignKey('service_id', 'services', 'id', ['constraint' => 'billings_service_id_fkey'])
            ->update();

        $this->table('borrowed_equipments')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'borrowed_equipments_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'borrowed_equipments_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'borrowed_equipments_customer_id_fkey'])
            ->addForeignKey('equipment_type_id', 'equipment_types', 'id', [
                'constraint' => 'borrowed_equipments_equipment_type_id_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'borrowed_equipments_modified_by_fkey'])
            ->update();

        $this->table('commissions')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'commissions_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'commissions_modified_by_fkey'])
            ->update();

        $this->table('connection_profiles')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'connection_profiles_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'connection_profiles_modified_by_fkey'])
            ->update();

        $this->table('contract_proposals')
            ->addForeignKey('customer_proposal_id', 'customer_proposals', 'id', [
                'delete' => 'RESTRICT',
                'constraint' => 'contract_proposals_customer_proposal_id_fkey',
            ])
            ->addForeignKey('applied_by', 'users', 'id', ['constraint' => 'contract_version_proposals_applied_by_fkey'])
            ->addForeignKey('contract_id', 'contracts', 'id', [
                'constraint' => 'contract_version_proposals_contract_id_fkey',
            ])
            ->addForeignKey('contract_version_id', 'contract_versions', 'id', [
                'constraint' => 'contract_version_proposals_contract_version_id_fkey',
            ])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'contract_version_proposals_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', [
                'constraint' => 'contract_version_proposals_modified_by_fkey',
            ])
            ->addForeignKey('revoked_by', 'users', 'id', ['constraint' => 'contract_version_proposals_revoked_by_fkey'])
            ->addForeignKey('terminates_contract_version_id', 'contract_versions', 'id', [
                'constraint' => 'contract_version_proposals_terminates_contract_version_id_fk',
            ])
            ->update();

        $this->table('contract_states')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'contract_states_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'contract_states_modified_by_fkey'])
            ->addForeignKey('requires_open_customer_task_type_id', 'task_types', 'id', [
                'constraint' => 'contract_states_requires_open_customer_task_type_id_fkey',
            ])
            ->addForeignKey('requires_open_task_type_id', 'task_types', 'id', [
                'constraint' => 'contract_states_requires_open_task_type_id_fkey',
            ])
            ->update();

        $this->table('contract_versions')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'contract_versions_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'contract_versions_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'contract_versions_modified_by_fkey'])
            ->update();

        $this->table('contracts')
            ->addForeignKey('commission_id', 'commissions', 'id', ['constraint' => 'contracts_commission_id_fkey'])
            ->addForeignKey('contract_state_id', 'contract_states', 'id', [
                'constraint' => 'contracts_contract_state_id_fkey',
            ])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'contracts_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'contracts_customer_id_fkey'])
            ->addForeignKey('installation_address_id', 'addresses', 'id', [
                'constraint' => 'contracts_installation_address_id_fkey',
            ])
            ->addForeignKey('installation_technician_id', 'customers', 'id', [
                'constraint' => 'contracts_installation_technician_id_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'contracts_modified_by_fkey'])
            ->addForeignKey('service_type_id', 'service_types', 'id', [
                'constraint' => 'contracts_service_type_id_fkey',
            ])
            ->addForeignKey('uninstallation_technician_id', 'customers', 'id', [
                'constraint' => 'contracts_uninstallation_technician_id_fkey',
            ])
            ->update();

        $this->table('countries')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'countries_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'countries_modified_by_fkey'])
            ->update();

        $this->table('customer_labels')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'customer_labels_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'customer_labels_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'customer_labels_customer_id_fkey'])
            ->addForeignKey('label_id', 'labels', 'id', ['constraint' => 'customer_labels_label_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'customer_labels_modified_by_fkey'])
            ->update();

        $this->table('customer_messages')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'customer_messages_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'customer_messages_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'customer_messages_modified_by_fkey'])
            ->update();

        $this->table('customer_proposals')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'customer_proposals_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'customer_proposals_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'customer_proposals_modified_by_fkey'])
            ->addForeignKey('revoked_by', 'users', 'id', ['constraint' => 'customer_proposals_revoked_by_fkey'])
            ->update();

        $this->table('customers')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'customers_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'customers_modified_by_fkey'])
            ->addForeignKey('accounting_profile_id', 'accounting_profiles', 'id', [
                'constraint' => 'customers_tax_rate_id_fkey',
            ])
            ->update();

        $this->table('dealer_commissions')
            ->addForeignKey('commission_id', 'commissions', 'id', [
                'constraint' => 'dealer_commissions_commission_id_fkey',
            ])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'dealer_commissions_created_by_fkey'])
            ->addForeignKey('dealer_id', 'customers', 'id', ['constraint' => 'dealer_commissions_dealer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'dealer_commissions_modified_by_fkey'])
            ->update();

        $this->table('documentation_types')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'documentation_types_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'documentation_types_modified_by_fkey'])
            ->update();

        $this->table('documentations')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'documentations_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'documentations_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'documentations_customer_id_fkey'])
            ->addForeignKey('documentation_type_id', 'documentation_types', 'id', [
                'constraint' => 'documentations_documentation_type_id_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'documentations_modified_by_fkey'])
            ->update();

        $this->table('emails')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'emails_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'emails_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'emails_modified_by_fkey'])
            ->update();

        $this->table('equipment_types')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'equipment_types_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'equipment_types_modified_by_fkey'])
            ->update();

        $this->table('failed_password_attempts')
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'failed_password_attempts_user_id_fkey'])
            ->update();

        $this->table('fulltext_search_customers')
            ->addForeignKey('customer_id', 'customers', 'id', [
                'delete' => 'CASCADE',
                'constraint' => 'fulltext_search_customers_customer_id_fkey',
            ])
            ->update();

        $this->table('historical_connections')
            ->addForeignKey('contract_id', 'contracts', 'id', [
                'constraint' => 'historical_connections_contract_id_fkey',
            ])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'historical_connections_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', [
                'constraint' => 'historical_connections_customer_id_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'historical_connections_modified_by_fkey'])
            ->update();

        $this->table('ip_addresses')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'ips_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'ips_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'ips_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'ips_modified_by_fkey'])
            ->update();

        $this->table('ip_networks')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'ip_networks_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'ip_networks_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'ip_networks_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'ip_networks_modified_by_fkey'])
            ->update();

        $this->table('labels')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'labels_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'labels_modified_by_fkey'])
            ->update();

        $this->table('logins')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'logins_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'logins_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'logins_modified_by_fkey'])
            ->update();

        $this->table('phones')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'phones_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'phones_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'phones_modified_by_fkey'])
            ->update();

        $this->table('removed_ip_addresses')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'removed_ips_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'removed_ips_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'removed_ips_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'removed_ips_modified_by_fkey'])
            ->addForeignKey('removed_by', 'users', 'id', ['constraint' => 'removed_ips_removed_by_fkey'])
            ->update();

        $this->table('removed_ip_networks')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'removed_ip_networks_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'removed_ip_networks_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'removed_ip_networks_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'removed_ip_networks_modified_by_fkey'])
            ->addForeignKey('removed_by', 'users', 'id', ['constraint' => 'removed_ip_networks_removed_by_fkey'])
            ->update();

        $this->table('service_overrides')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'service_overrides_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'service_overrides_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'service_overrides_modified_by_fkey'])
            ->addForeignKey('revoked_by', 'users', 'id', ['constraint' => 'service_overrides_revoked_by_fkey'])
            ->addForeignKey('service_id', 'services', 'id', ['constraint' => 'service_overrides_service_id_fkey'])
            ->update();

        $this->table('service_types')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'service_types_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'service_types_modified_by_fkey'])
            ->update();

        $this->table('services')
            ->addForeignKey('connection_profile_id', 'connection_profiles', 'id', [
                'constraint' => 'services_connection_profile_id_fkey',
            ])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'services_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'services_modified_by_fkey'])
            ->addForeignKey('service_type_id', 'service_types', 'id', ['constraint' => 'services_service_type_id_fkey'])
            ->update();

        $this->table('social_accounts')
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'social_accounts_user_id_fkey'])
            ->update();

        $this->table('sold_equipments')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'sold_equipments_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'sold_equipments_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'sold_equipments_customer_id_fkey'])
            ->addForeignKey('equipment_type_id', 'equipment_types', 'id', [
                'constraint' => 'sold_equipments_equipment_type_id_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'sold_equipments_modified_by_fkey'])
            ->update();

        $this->table('task_collaborators')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'task_collaborators_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'task_collaborators_modified_by_fkey'])
            ->addForeignKey('task_id', 'tasks', 'id', [
                'delete' => 'CASCADE',
                'constraint' => 'task_collaborators_task_id_fkey',
            ])
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'task_collaborators_user_id_fkey'])
            ->update();

        $this->table('task_states')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'task_states_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'task_states_modified_by_fkey'])
            ->update();

        $this->table('task_types')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'task_types_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'task_types_modified_by_fkey'])
            ->update();

        $this->table('tasks')
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'tasks_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'tasks_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'tasks_customer_id_fkey'])
            ->addForeignKey('dealer_id', 'customers', 'id', ['constraint' => 'tasks_dealer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'tasks_modified_by_fkey'])
            ->addForeignKey('task_state_id', 'task_states', 'id', ['constraint' => 'tasks_task_state_id_fkey'])
            ->addForeignKey('task_type_id', 'task_types', 'id', ['constraint' => 'tasks_task_type_id_fkey'])
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'tasks_user_id_fkey'])
            ->update();

        $this->table('users')
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'users_customer_id_fkey'])
            ->update();
    }
}
