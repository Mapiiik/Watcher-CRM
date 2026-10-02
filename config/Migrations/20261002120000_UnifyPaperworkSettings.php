<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UnifyPaperworkSettings extends BaseMigration
{
    /**
     * What moves where in the contracts' settings, as dotted paths inside that one block.
     *
     * @var array<string, string>
     */
    private const CONTRACTS = [
        'unsigned.consider_from' => 'paperwork.consider_from',
        'unsigned.anchor' => 'paperwork.anchor',
        'unsigned.notifications.enabled' => 'paperwork.unsigned.notifications.enabled',
        'unsigned.notifications.after_installation_days' => 'paperwork.unsigned.notifications.after_anchor_days',
        'unsigned.notifications.after_valid_from_days' => 'paperwork.unsigned.notifications.after_start_days',
        'unsigned.notifications.reminder_days' => 'paperwork.unsigned.notifications.days',
        'unsigned.notifications.remind_daily_after' => 'paperwork.unsigned.notifications.daily_after',
        'unsigned.notifications.channels' => 'paperwork.unsigned.notifications.channels',
        'unsigned.notifications.types' => 'paperwork.unsigned.notifications.types',
        'unsigned.blocking.enabled' => 'paperwork.unsigned.blocking.enabled',
        'unsigned.blocking.after_installation_days' => 'paperwork.unsigned.blocking.after_anchor_days',
        'unsigned.blocking.after_valid_from_days' => 'paperwork.unsigned.blocking.after_start_days',
        'unsigned.emails' => 'paperwork.unsigned.emails',
        'unsigned.sms' => 'paperwork.unsigned.sms',
        'checks.signature_expected_within_months'
            => 'paperwork.unsigned.thresholds.signature_expected_within_months',
        'checks.unapplied_proposal_within_days' => 'paperwork.unapplied.before_effective_days',
        'proposals.unsent_within_days' => 'paperwork.unsent.before_effective_days',
        'proposals.unanswered_after_days' => 'paperwork.unanswered.after_sending_days',
        'documents.unfiled_after_days' => 'paperwork.unfiled.after_signature_days',
    ];

    /**
     * The same for the customers' side, which keeps the three waits and nothing that disconnects.
     *
     * @var array<string, string>
     */
    private const CUSTOMERS = [
        'proposals.unsent_within_days' => 'paperwork.unsent.before_effective_days',
        'proposals.unanswered_after_days' => 'paperwork.unanswered.after_sending_days',
        'documents.unfiled_after_days' => 'paperwork.unfiled.after_signature_days',
    ];

    /**
     * The objects a move can leave behind with nothing in them, innermost first.
     *
     * @var array<string, list<string>>
     */
    private const EMPTIED = [
        'contracts' => [
            '{unsigned,notifications}',
            '{unsigned,blocking}',
            '{unsigned}',
            '{proposals}',
            '{documents}',
            '{checks}',
            '{paperwork,unsigned,notifications}',
            '{paperwork,unsigned,blocking}',
            '{paperwork,unsigned,thresholds}',
            '{paperwork,unsigned}',
            '{paperwork,unsent}',
            '{paperwork,unanswered}',
            '{paperwork,unfiled}',
            '{paperwork,unapplied}',
            '{paperwork}',
        ],
        'customers' => [
            '{proposals}',
            '{documents}',
            '{paperwork,unsent}',
            '{paperwork,unanswered}',
            '{paperwork,unfiled}',
            '{paperwork}',
        ],
    ];

    /**
     * Up Method.
     *
     * Chasing an unsigned contract and chasing a paper nobody has sent are one job to the office,
     * and their settings were spread over four blocks with two names for the same kind of wait. One
     * block now holds the family, in the words the invoice reminders already use. What somebody has
     * set is kept, under the new name.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (['contracts' => self::CONTRACTS, 'customers' => self::CUSTOMERS] as $key => $moves) {
            foreach ($moves as $from => $to) {
                $this->move($key, $from, $to);
            }

            $this->dropEmptied($key);
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        foreach (['contracts' => self::CONTRACTS, 'customers' => self::CUSTOMERS] as $key => $moves) {
            foreach (array_reverse($moves, true) as $from => $to) {
                $this->move($key, $to, $from);
            }

            $this->dropEmptied($key);
        }
    }

    /**
     * Moves one value inside one settings block, where it was set at all.
     *
     * @param string $key Which block of the core settings - the row in `settings`.
     * @param string $from Where it is, dotted.
     * @param string $to Where it goes, dotted.
     * @return void
     */
    private function move(string $key, string $from, string $to): void
    {
        // The table is the Settings plugin's, and a database built from nothing - the test one -
        // runs the application's migrations before the plugin has made it. Nothing is set there.
        if (!$this->hasTable('settings')) {
            return;
        }

        $source = '{' . str_replace('.', ',', $from) . '}';
        $target = explode('.', $to);

        // jsonb_set only makes the last key of a path, so every object on the way is made first.
        $value = "(value #- '" . $source . "')";
        $depths = count($target);
        for ($depth = 1; $depth < $depths; $depth++) {
            $path = '{' . implode(',', array_slice($target, 0, $depth)) . '}';
            $value = sprintf("jsonb_set(%1\$s, '%2\$s', COALESCE(%1\$s #> '%2\$s', '{}'::jsonb))", $value, $path);
        }

        $this->execute(sprintf(
            "UPDATE settings SET value = jsonb_set(%s, '{%s}', value #> '%s')"
            . " WHERE plugin = 'core' AND key = '%s' AND value #> '%s' IS NOT NULL",
            $value,
            implode(',', $target),
            $source,
            $key,
            $source,
        ));
    }

    /**
     * Throws away the objects the moves left with nothing in them.
     *
     * Innermost first, so that a parent whose last child has just gone is itself seen to be empty.
     *
     * @param string $key Which block of the core settings.
     * @return void
     */
    private function dropEmptied(string $key): void
    {
        if (!$this->hasTable('settings')) {
            return;
        }

        foreach (self::EMPTIED[$key] as $path) {
            $this->execute(sprintf(
                "UPDATE settings SET value = value #- '%1\$s'"
                . " WHERE plugin = 'core' AND key = '%2\$s' AND value #> '%1\$s' = '{}'::jsonb",
                $path,
                $key,
            ));
        }
    }
}
