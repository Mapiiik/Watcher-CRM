<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * How far into the month the worker considers themselves done.
 *
 * Submitting closes a month whole and late, so until then everything written stays open to being
 * rewritten. A day named here is closed on its own: what is on it stays as it is, and the days
 * after it go on being filled in. The worker moves the day forward, an admin also back.
 */
class AddClosedUntilToWorkReports extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('work_reports')
            ->addColumn('closed_until', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'return_reason',
            ])
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('work_reports')
            ->removeColumn('closed_until')
            ->update();
    }
}
