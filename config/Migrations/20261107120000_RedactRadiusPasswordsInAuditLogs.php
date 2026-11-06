<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * RADIUS passwords out of the audit log.
 *
 * Accounts, their checks and the NAS are now logged with their passwords and secrets shown as
 * `****`. The rows written before keep the values, so those are overwritten the same way, also
 * inside the checks an account used to be logged with. The values cannot be put back.
 */
class RedactRadiusPasswordsInAuditLogs extends BaseMigration
{
    /**
     * The fields to hide, by the sources that log them.
     *
     * @var array<string, list<string>>
     */
    private const SECRETS = [
        'accounts' => ['password'],
        'Radius.Accounts' => ['password'],
        'nas' => ['secret'],
        'Radius.Nas' => ['secret'],
    ];

    /**
     * What a check logs itself under.
     *
     * @var list<string>
     */
    private const CHECKS = ['radcheck', 'Radius.Radcheck'];

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (['original', 'changed'] as $column) {
            foreach (self::SECRETS as $source => $fields) {
                foreach ($fields as $field) {
                    // `->` rather than the jsonb `?`, which PDO takes for a placeholder
                    $this->execute(sprintf(
                        "UPDATE audit_logs SET %1\$s = %1\$s || jsonb_build_object('%2\$s', '****')
                        WHERE source = '%3\$s'
                            AND jsonb_typeof(%1\$s -> '%2\$s') = 'string'
                            AND %1\$s ->> '%2\$s' <> '****'",
                        $column,
                        $field,
                        $source,
                    ));
                }
            }

            foreach (self::CHECKS as $source) {
                // the attribute comes with a new or a deleted check, without it the value may be anything
                $this->execute(sprintf(
                    "UPDATE audit_logs SET %1\$s = %1\$s || jsonb_build_object('value', '****')
                    WHERE source = '%2\$s'
                        AND jsonb_typeof(%1\$s -> 'value') = 'string'
                        AND %1\$s ->> 'value' <> '****'
                        AND COALESCE(original ->> 'attribute', changed ->> 'attribute', 'Cleartext-Password')
                            = 'Cleartext-Password'",
                    $column,
                    $source,
                ));
            }

            foreach (array_keys(self::SECRETS) as $source) {
                $this->execute(sprintf(
                    "UPDATE audit_logs SET %1\$s = jsonb_set(%1\$s, '{radcheck}', (
                        SELECT jsonb_agg(
                            CASE WHEN checks.value ->> 'attribute' = 'Cleartext-Password'
                                THEN checks.value || jsonb_build_object('value', '****')
                                ELSE checks.value
                            END
                            ORDER BY checks.position
                        )
                        FROM jsonb_array_elements(%1\$s -> 'radcheck') WITH ORDINALITY AS checks(value, position)
                    ))
                    WHERE source = '%2\$s'
                        AND jsonb_typeof(%1\$s -> 'radcheck') = 'array'
                        AND jsonb_array_length(%1\$s -> 'radcheck') > 0",
                    $column,
                    $source,
                ));
            }
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        // what was overwritten is gone
    }
}
