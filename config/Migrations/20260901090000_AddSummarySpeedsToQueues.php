<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSummarySpeedsToQueues extends BaseMigration
{
    /**
     * Change Method.
     *
     * The contract summary has to state a commonly available and a minimum speed beside the
     * advertised one, and the tariff has only ever carried the advertised figure. These four
     * hold what the published speed sheet declares, in kbps like the columns beside them.
     *
     * All of them are nullable, and empty means the tariff has not declared its own number -
     * the entity then derives one from the advertised speed. Empty is therefore an answer,
     * not a gap waiting to be filled.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('queues');
        $table->addColumn('speed_down_common', 'biginteger', [
            'default' => null,
            'limit' => 20,
            'null' => true,
            'comment' => 'Declared commonly available download speed in kbps, empty derives from speed_down',
        ]);
        $table->addColumn('speed_up_common', 'biginteger', [
            'default' => null,
            'limit' => 20,
            'null' => true,
            'comment' => 'Declared commonly available upload speed in kbps, empty derives from speed_up',
        ]);
        $table->addColumn('speed_down_minimum', 'biginteger', [
            'default' => null,
            'limit' => 20,
            'null' => true,
            'comment' => 'Declared minimum download speed in kbps, empty derives from speed_down',
        ]);
        $table->addColumn('speed_up_minimum', 'biginteger', [
            'default' => null,
            'limit' => 20,
            'null' => true,
            'comment' => 'Declared minimum upload speed in kbps, empty derives from speed_up',
        ]);
        $table->update();
    }
}
