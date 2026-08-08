<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MovePohodaAccountingUnitSetting extends BaseMigration
{
    /**
     * Up Method.
     *
     * Carries a stored Pohoda accounting unit over to the path the plugin now reads it from.
     * Left where it was it would be ignored, and the requests would go to whichever unit the
     * environment names.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(<<<'SQL'
            UPDATE settings
            SET value = jsonb_set(
                jsonb_set(
                    value #- '{providers,pohoda,issuer}',
                    '{providers,pohoda,api}',
                    COALESCE(value #> '{providers,pohoda,api}', '{}'::jsonb),
                    true
                ),
                '{providers,pohoda,api,accounting_unit}',
                value #> '{providers,pohoda,issuer,identity_number}',
                true
            )
            WHERE plugin = 'bookkeeping'
              AND key = 'accounting'
              AND value #> '{providers,pohoda,issuer,identity_number}' IS NOT NULL
            SQL);
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute(<<<'SQL'
            UPDATE settings
            SET value = jsonb_set(
                value #- '{providers,pohoda,api,accounting_unit}',
                '{providers,pohoda,issuer}',
                jsonb_build_object(
                    'identity_number',
                    value #> '{providers,pohoda,api,accounting_unit}'
                ),
                true
            )
            WHERE plugin = 'bookkeeping'
              AND key = 'accounting'
              AND value #> '{providers,pohoda,api,accounting_unit}' IS NOT NULL
            SQL);
    }
}
