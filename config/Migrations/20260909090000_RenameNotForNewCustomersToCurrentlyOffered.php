<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameNotForNewCustomersToCurrentlyOffered extends BaseMigration
{
    /**
     * Up Method.
     *
     * The one flag on a service that was written backwards. Asked as "not for new customers" it
     * read as a double negative wherever it was shown - a listing saying "Not For New Customers:
     * No" is a riddle - and it was the only boolean in the application phrased against itself.
     *
     * It also said less than it meant. What the office marks this way is a tariff that is not on
     * offer any more at all, to anybody; it is only ever *sold* to new customers, so keeping it
     * off their list was as far as the old name reached.
     *
     * Renamed rather than added beside: every reading left behind then stops compiling instead of
     * quietly meaning the opposite.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('services')
            ->renameColumn('not_for_new_customers', 'currently_offered')
            ->update();

        $this->execute('UPDATE services SET currently_offered = NOT currently_offered');

        $this->table('services')
            ->changeColumn('currently_offered', 'boolean', [
                'default' => true,
                'null' => false,
                'comment' => 'Whether the service is still on offer; what is not is left out of the lists',
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
        $this->table('services')
            ->renameColumn('currently_offered', 'not_for_new_customers')
            ->update();

        $this->execute('UPDATE services SET not_for_new_customers = NOT not_for_new_customers');

        $this->table('services')
            ->changeColumn('not_for_new_customers', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->update();
    }
}
