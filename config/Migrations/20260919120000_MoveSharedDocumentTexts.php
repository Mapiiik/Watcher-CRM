<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MoveSharedDocumentTexts extends BaseMigration
{
    /**
     * What moves where, as paths in the documents' settings.
     *
     * @var array<string, string>
     */
    private const MOVES = [
        'contracts.billing' => 'common.billing',
        'contracts.contract.sections.billing_pricelist' => 'common.billing.sections.billing_pricelist',
        'contracts.contract.sections.billing_individual' => 'common.billing.sections.billing_individual',
        'contracts.contract.sections.billing_future_pricelist' => 'common.billing.sections.billing_future_pricelist',
        'contracts.contract.sections.billing_future_individual' => 'common.billing.sections.billing_future_individual',
        'contracts.contract.sections.payment_info' => 'common.payment.heading',
        'contracts.contract.texts.reverse_charge_clause' => 'common.payment.reverse_charge_clause',
        'contracts.contract.texts.standing_order_note' => 'common.payment.standing_order_note',
    ];

    /**
     * Up Method.
     *
     * The table of services and the payment details are printed on the list of what a customer has
     * as well as on the contract, so their texts are shared rather than the contract's. What
     * somebody has rewritten is kept, under the new name.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (self::MOVES as $from => $to) {
            $this->move($from, $to);
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        foreach (array_reverse(self::MOVES, true) as $from => $to) {
            $this->move($to, $from);
        }

        // The blocks made on the way up, left empty by the moves back.
        if ($this->hasTable('settings')) {
            foreach (['{contracts,billing,sections}', '{common,payment}'] as $path) {
                $this->execute(sprintf(
                    "UPDATE settings SET value = value #- '%1\$s'"
                    . " WHERE plugin = 'core' AND key = 'documents' AND value #> '%1\$s' = '{}'::jsonb",
                    $path,
                ));
            }
        }
    }

    /**
     * Moves one value among the documents' settings, where it was set at all.
     *
     * @param string $from Where it is, dotted.
     * @param string $to Where it goes, dotted.
     * @return void
     */
    private function move(string $from, string $to): void
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
            . " WHERE plugin = 'core' AND key = 'documents' AND value #> '%s' IS NOT NULL",
            $value,
            implode(',', $target),
            $source,
            $source,
        ));
    }
}
