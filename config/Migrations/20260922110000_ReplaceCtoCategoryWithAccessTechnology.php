<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ReplaceCtoCategoryWithAccessTechnology extends BaseMigration
{
    /**
     * How the old categories read as technologies.
     *
     * The Czech installation filled in ČTÚ's annex identifiers, the Croatian one the old system's
     * infrastructure type. FTTB here is fibre to the switch in the building and copper Ethernet
     * from it, FTTH is PON with the customer's ONU. That is how these networks are built, which is
     * why the table lives in a migration and not in the code.
     */
    private const MAP = [
        's2_wifi' => 'fwa_unlicensed',
        's2_fttb' => 'fttb_ethernet',
        's2_ftth' => 'ftth_p2mp_pon',
        's2_catv' => 'catv_docsis30',
        'FWA-WiFi' => 'fwa_unlicensed',
    ];

    /**
     * The way back, which cannot tell the two wireless spellings apart and takes ČTÚ's.
     */
    private const BACK = [
        'fwa_unlicensed' => 's2_wifi',
        'fttb_ethernet' => 's2_fttb',
        'ftth_p2mp_pon' => 's2_ftth',
        'catv_docsis30' => 's2_catv',
    ];

    /**
     * Up Method.
     *
     * A profile says how it is delivered rather than which box of one regulator's form it goes in,
     * and each report works the category out for itself. A category the map does not know is
     * named on the way and left empty, for the operator to fill in.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('connection_profiles')
            ->addColumn('access_technology', 'string', [
                'default' => null,
                'limit' => 32,
                'null' => true,
                'after' => 'speed_up_minimum',
            ])
            ->update();

        $this->execute(sprintf(
            'UPDATE connection_profiles SET access_technology = %s WHERE cto_category IS NOT NULL',
            $this->caseOf('cto_category', self::MAP),
        ));

        foreach (
            $this->fetchAll(
                'SELECT radius_group, cto_category FROM connection_profiles'
                . ' WHERE cto_category IS NOT NULL AND access_technology IS NULL',
            ) as $unknown
        ) {
            $this->getIo()?->warning(sprintf(
                'Connection profile %s: CTO category "%s" has no technology, left empty.',
                $unknown['radius_group'],
                $unknown['cto_category'],
            ));
        }

        if ($this->hasTable('contract_proposals')) {
            $this->rewriteProposals('cto_category', 'access_technology', self::MAP);
        }

        $this->table('connection_profiles')
            ->removeColumn('cto_category')
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $back = self::BACK;

        $this->table('connection_profiles')
            ->addColumn('cto_category', 'string', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->update();

        $this->execute(sprintf(
            'UPDATE connection_profiles SET cto_category = %s WHERE access_technology IS NOT NULL',
            $this->caseOf('access_technology', $back),
        ));

        if ($this->hasTable('contract_proposals')) {
            $this->rewriteProposals('access_technology', 'cto_category', $back);
        }

        $this->table('connection_profiles')
            ->removeColumn('access_technology')
            ->update();
    }

    /**
     * A CASE turning one set of values into the other, null for any it does not know.
     *
     * @param string $column Which column or expression.
     * @param array<string, string> $map From what to what.
     * @return string
     */
    private function caseOf(string $column, array $map): string
    {
        $when = '';
        foreach ($map as $from => $to) {
            $when .= sprintf(" WHEN '%s' THEN '%s'", $from, $to);
        }

        return sprintf('CASE %s%s ELSE NULL END', $column, $when);
    }

    /**
     * Moves the key in every connection profile the proposals keep, translating its value.
     *
     * @param string $from The key it is under now.
     * @param string $to The key it is to be under.
     * @param array<string, string> $map From which value to which.
     * @return void
     */
    private function rewriteProposals(string $from, string $to, array $map): void
    {
        $this->execute(sprintf(
            <<<'SQL'
            CREATE FUNCTION pg_temp.rekey_profile(doc jsonb) RETURNS jsonb LANGUAGE plpgsql AS $$
            DECLARE result jsonb; k text; v jsonb;
            BEGIN
                IF jsonb_typeof(doc) = 'array' THEN
                    SELECT COALESCE(jsonb_agg(pg_temp.rekey_profile(e) ORDER BY i), '[]'::jsonb) INTO result
                    FROM jsonb_array_elements(doc) WITH ORDINALITY AS a(e, i);
                    RETURN result;
                END IF;
                IF jsonb_typeof(doc) <> 'object' THEN
                    RETURN doc;
                END IF;
                result := '{}'::jsonb;
                FOR k, v IN SELECT * FROM jsonb_each(doc) LOOP
                    IF k = 'connection_profile' AND jsonb_typeof(v) = 'object' AND v ? '%1$s' THEN
                        result := result || jsonb_build_object(k, (v - '%1$s')
                            || jsonb_build_object('%2$s', to_jsonb(%3$s)));
                    ELSE
                        result := result || jsonb_build_object(k, pg_temp.rekey_profile(v));
                    END IF;
                END LOOP;
                RETURN result;
            END $$
            SQL,
            $from,
            $to,
            $this->caseOf("v->>'" . $from . "'", $map),
        ));

        $this->execute(sprintf(
            'UPDATE contract_proposals'
            . ' SET snapshot = pg_temp.rekey_profile(snapshot), changes = pg_temp.rekey_profile(changes)'
            . " WHERE snapshot::text LIKE '%%\"%1\$s\"%%' OR changes::text LIKE '%%\"%1\$s\"%%'",
            $from,
        ));

        $this->execute('DROP FUNCTION pg_temp.rekey_profile(jsonb)');
    }
}
