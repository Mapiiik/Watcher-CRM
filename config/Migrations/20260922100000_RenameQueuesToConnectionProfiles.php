<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameQueuesToConnectionProfiles extends BaseMigration
{
    /**
     * Up Method.
     *
     * What the table holds is a connection profile - the speeds, the limits and the technology a
     * tariff is delivered with - and a queue was only ever how the router enforced it. The column
     * that named the queue stays what it is, the RADIUS group, and the human caption takes the
     * name. A profile without a caption is named after its group, so that none is left nameless.
     *
     * The proposals keep the profile of every service they quote, and the audit log files each
     * change under the table it was made to. Both are moved with it. Both are asked for rather
     * than assumed, because a database built from nothing does not necessarily have them yet.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('queues')
            ->rename('connection_profiles')
            ->update();

        $this->table('connection_profiles')
            ->renameColumn('name', 'radius_group')
            ->renameColumn('caption', 'name')
            ->update();

        $this->table('services')
            ->renameColumn('queue_id', 'connection_profile_id')
            ->update();

        // The group's not-null check first, so that the name's own can take the name it frees.
        $this->renameConstraints(
            'connection_profiles',
            'queues_name_not_null',
            'connection_profiles_radius_group_not_null',
        );
        $this->renameConstraints('connection_profiles', 'queues_', 'connection_profiles_');
        $this->renameConstraints('services', 'services_queue_id_', 'services_connection_profile_id_');

        $this->execute("UPDATE connection_profiles SET name = radius_group WHERE name IS NULL OR name = ''");
        $this->execute('ALTER TABLE connection_profiles ALTER COLUMN name SET NOT NULL');

        if ($this->hasTable('contract_proposals')) {
            $this->rewriteProposals(up: true);
        }

        if ($this->hasTable('audit_logs')) {
            $this->execute("UPDATE audit_logs SET source = 'connection_profiles' WHERE source = 'queues'");
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        if ($this->hasTable('audit_logs')) {
            $this->execute("UPDATE audit_logs SET source = 'queues' WHERE source = 'connection_profiles'");
        }

        if ($this->hasTable('contract_proposals')) {
            $this->rewriteProposals(up: false);
        }

        // The caption was optional. What the up filled in from the group goes back to being empty.
        $this->execute('ALTER TABLE connection_profiles ALTER COLUMN name DROP NOT NULL');
        $this->execute('UPDATE connection_profiles SET name = NULL WHERE name = radius_group');

        $this->renameConstraints('services', 'services_connection_profile_id_', 'services_queue_id_');
        $this->renameConstraints(
            'connection_profiles',
            'connection_profiles_radius_group_not_null',
            'queues_name_not_null',
        );
        $this->renameConstraints('connection_profiles', 'connection_profiles_', 'queues_');

        $this->table('services')
            ->renameColumn('connection_profile_id', 'queue_id')
            ->update();

        $this->table('connection_profiles')
            ->renameColumn('name', 'caption')
            ->renameColumn('radius_group', 'name')
            ->update();

        $this->table('connection_profiles')
            ->rename('queues')
            ->update();
    }

    /**
     * Renames the table's constraints, and with them their indexes, from one prefix to another.
     *
     * Looked up rather than listed: which constraints there are depends on the Postgres version
     * that made the database, and the newer ones name even the not-null checks.
     *
     * @param string $table Whose constraints.
     * @param string $from The prefix they carry now.
     * @param string $to The prefix they are to carry.
     * @return void
     */
    private function renameConstraints(string $table, string $from, string $to): void
    {
        $this->execute(sprintf(
            <<<'SQL'
            DO $$
            DECLARE c record;
            BEGIN
                FOR c IN
                    SELECT conname FROM pg_constraint
                    WHERE conrelid = '%1$s'::regclass AND starts_with(conname, '%2$s')
                LOOP
                    EXECUTE format(
                        'ALTER TABLE %1$s RENAME CONSTRAINT %%I TO %%I',
                        c.conname,
                        '%3$s' || substr(c.conname, length('%2$s') + 1)
                    );
                END LOOP;
            END $$
            SQL,
            $table,
            $from,
            $to,
        ));
    }

    /**
     * Moves the profile the proposals keep with each service to its new key and column names.
     *
     * Wherever it sits: the snapshot keeps it under each billing, and the changes under each line
     * that chose a different service. The function walks the whole document so it does not have
     * to know which.
     *
     * @param bool $up Which way.
     * @return void
     */
    private function rewriteProposals(bool $up): void
    {
        [$key, $newKey] = $up ? ['queue', 'connection_profile'] : ['connection_profile', 'queue'];
        $profile = $up
            ? "(p - 'name' - 'caption') || jsonb_build_object('radius_group', p->'name', 'name', "
                . "COALESCE(NULLIF(p->>'caption', ''), p->>'name'))"
            : "(p - 'name' - 'radius_group') || jsonb_build_object('name', p->'radius_group', 'caption', "
                . "CASE WHEN p->>'name' = p->>'radius_group' THEN NULL ELSE p->'name' END)";

        $this->execute(sprintf(
            <<<'SQL'
            CREATE FUNCTION pg_temp.rename_profile(doc jsonb) RETURNS jsonb LANGUAGE plpgsql AS $$
            DECLARE result jsonb; k text; v jsonb; p jsonb;
            BEGIN
                IF jsonb_typeof(doc) = 'array' THEN
                    SELECT COALESCE(jsonb_agg(pg_temp.rename_profile(e) ORDER BY i), '[]'::jsonb) INTO result
                    FROM jsonb_array_elements(doc) WITH ORDINALITY AS a(e, i);
                    RETURN result;
                END IF;
                IF jsonb_typeof(doc) <> 'object' THEN
                    RETURN doc;
                END IF;
                result := '{}'::jsonb;
                FOR k, v IN SELECT * FROM jsonb_each(doc) LOOP
                    IF k = '%1$s' AND jsonb_typeof(v) = 'object' THEN
                        p := v;
                        result := result || jsonb_build_object('%2$s', %3$s);
                    ELSIF k = '%1$s' THEN
                        -- a service without a profile keeps its null under the new key
                        result := result || jsonb_build_object('%2$s', v);
                    ELSE
                        result := result || jsonb_build_object(k, pg_temp.rename_profile(v));
                    END IF;
                END LOOP;
                RETURN result;
            END $$
            SQL,
            $key,
            $newKey,
            $profile,
        ));

        $this->execute(sprintf(
            'UPDATE contract_proposals'
            . ' SET snapshot = pg_temp.rename_profile(snapshot), changes = pg_temp.rename_profile(changes)'
            . " WHERE snapshot::text LIKE '%%\"%1\$s\"%%' OR changes::text LIKE '%%\"%1\$s\"%%'",
            $key,
        ));

        $this->execute('DROP FUNCTION pg_temp.rename_profile(jsonb)');
    }
}
