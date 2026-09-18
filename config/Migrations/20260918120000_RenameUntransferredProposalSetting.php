<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameUntransferredProposalSetting extends BaseMigration
{
    /**
     * Up Method.
     *
     * Carrying a proposal over is called applying its changes now, and the check that looks for
     * the ones nobody applied is named after that. How far ahead it looks is kept where somebody
     * set it, under the new name.
     *
     * @return void
     */
    public function up(): void
    {
        $this->move('untransferred_proposal_within_days', 'unapplied_proposal_within_days');
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->move('unapplied_proposal_within_days', 'untransferred_proposal_within_days');
    }

    /**
     * Moves one key among the contract checks' settings, where it was set at all.
     *
     * @param string $from The name it has.
     * @param string $to The name it gets.
     * @return void
     */
    private function move(string $from, string $to): void
    {
        // The table is the Settings plugin's, and a database built from nothing - the test one -
        // runs the application's migrations before the plugin has made it. Nothing is set there.
        if (!$this->hasTable('settings')) {
            return;
        }

        $this->execute(sprintf(
            "UPDATE settings SET value = jsonb_set(value #- '{checks,%1\$s}', '{checks,%2\$s}',"
            . " value #> '{checks,%1\$s}')"
            . " WHERE plugin = 'core' AND key = 'contracts' AND value #> '{checks,%1\$s}' IS NOT NULL",
            $from,
            $to,
        ));
    }
}
