<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Secrets out of the audit log.
 *
 * Accounts are now logged with their password hashes, one-time secrets and tokens shown as
 * `****`, and so are customer portal logins with their passwords. The rows written before keep
 * the values themselves, so those are overwritten the same way. The values cannot be put back.
 */
class RedactSecretsInAuditLogs extends BaseMigration
{
    /**
     * The fields to hide, by the sources that log them.
     *
     * @var array<string, list<string>>
     */
    private const SECRETS = [
        'users' => ['password', 'secret', 'api_token', 'token', 'login_token'],
        'AppUsers' => ['password', 'secret', 'api_token', 'token', 'login_token'],
        'logins' => ['password'],
        'Logins' => ['password'],
    ];

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (self::SECRETS as $source => $fields) {
            foreach ($fields as $field) {
                foreach (['original', 'changed'] as $column) {
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
