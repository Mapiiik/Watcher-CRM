<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * The consents of a customer are a yes or a no, and a new customer gets what the paper consent says
 * holds where nothing is ticked: the billing and the outages yes, the commercial communications no,
 * as silence is no consent. A consent to the processing of personal data is a no until the paper is
 * signed. The form for a new customer opens that way too, as it takes its defaults from the schema.
 *
 * A customer with nothing on file gets the same, which for the commercial communications and the
 * consent itself changes nothing, as an empty one counted as a no already. A yes or a no on file stays.
 */
class NewCustomersReceiveWhatTheConsentGivesWithoutATick extends BaseMigration
{
    /**
     * The flags and what holds without a tick.
     */
    private const DEFAULTS = [
        'agree_gdpr' => false,
        'agree_mailing_billing' => true,
        'agree_mailing_outages' => true,
        'agree_mailing_commercial' => false,
    ];

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('customers');
        foreach (self::DEFAULTS as $column => $default) {
            $this->getUpdateBuilder()
                ->update('customers')
                ->set($column, $default, 'boolean')
                ->where([$column . ' IS' => null])
                ->execute();
            $table->changeColumn($column, 'boolean', ['default' => $default, 'null' => false]);
        }
        $table->update();
    }

    /**
     * Down Method.
     *
     * The yes and no filled in where nothing was on file stay, as nothing tells them apart.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('customers');
        foreach (array_keys(self::DEFAULTS) as $column) {
            $table->changeColumn($column, 'boolean', ['default' => null, 'null' => true]);
        }
        $table->update();
    }
}
