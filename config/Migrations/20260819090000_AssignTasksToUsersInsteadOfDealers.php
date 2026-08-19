<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Tasks move from being held by a dealer to being held by a user.
 *
 * A task was assigned to a `customers` row marked as a dealer, which is not what the other
 * application does and not what the word means here any more: the people who hold tasks are the
 * people who sign in. The network management system has always assigned them to users, and the two
 * implementations are being brought together.
 *
 * `dealer_id` is left in place. It is what this migration read to fill `user_id`, and keeping it
 * one release means the mapping can be checked against its source before the source is gone.
 */
class AssignTasksToUsersInsteadOfDealers extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('tasks')
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'null' => true,
                'after' => 'dealer_id',
            ])
            ->update();

        $this->createUsersForDealersWithout();
        $this->failOnDuplicateUsernames();

        // A dealer with more than one user is not a thing this installation has, but the choice is
        // spelled out rather than left to the query planner: someone who can still sign in stands
        // for the dealer ahead of someone who cannot, and the older account ahead of the newer.
        $this->execute(<<<'SQL'
            UPDATE tasks t
            SET user_id = (
                SELECT u.id FROM users u
                WHERE u.customer_id = t.dealer_id
                ORDER BY u.active DESC, u.created, u.id
                LIMIT 1
            )
            WHERE t.dealer_id IS NOT NULL
            SQL);

        $this->table('tasks')
            ->addForeignKey('user_id', 'users', 'id')
            ->addIndex(['user_id'])
            ->update();
    }

    /**
     * Down Method.
     *
     * The users this migration created are left behind. They are disabled and cannot be signed in
     * to, so they cost nothing standing there; deleting them would instead risk taking with them
     * an account someone has since put to use.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('tasks')
            ->dropForeignKey('user_id')
            ->removeIndex(['user_id'])
            ->removeColumn('user_id')
            ->update();
    }

    /**
     * Give every dealer holding tasks a user to stand for them.
     *
     * Most dealers who hold tasks are somebody who signs in, and are matched by the `customer_id`
     * their account already carries. The rest are people who have left: their tasks are finished
     * and are kept for the record, and the record is worth nothing if it stops saying whose work it
     * was. They get an account that exists only to be named - disabled, and with a password no
     * input can hash to, so it is shut by both locks rather than one.
     *
     * @return void
     */
    private function createUsersForDealersWithout(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS unaccent');

        // The username follows the convention the existing accounts keep: surname, a dot, and the
        // given name, folded to ASCII. Two dealers of the same name would fold to the same
        // username, so the second and further get their position appended.
        $this->execute(<<<'SQL'
            WITH orphaned AS (
                SELECT DISTINCT c.id, c.first_name, c.last_name, c.company
                FROM tasks t
                JOIN customers c ON c.id = t.dealer_id
                WHERE t.dealer_id IS NOT NULL
                  AND NOT EXISTS (SELECT 1 FROM users u WHERE u.customer_id = c.id)
            ),
            named AS (
                SELECT o.*,
                    regexp_replace(
                        lower(unaccent(
                            coalesce(
                                nullif(concat_ws('.', o.last_name, o.first_name), ''),
                                o.company,
                                'dealer'
                            )
                        )),
                        '[^a-z0-9.]+', '', 'g'
                    ) AS base
                FROM orphaned o
            ),
            numbered AS (
                SELECT n.*, row_number() OVER (PARTITION BY n.base ORDER BY n.id) AS seq
                FROM named n
            )
            INSERT INTO users (
                username, password, first_name, last_name, active, role, customer_id,
                created, modified
            )
            SELECT
                CASE
                    WHEN seq = 1 AND NOT EXISTS (SELECT 1 FROM users u WHERE u.username = base)
                    THEN base
                    ELSE base || '.' || seq::text
                END,
                '*',
                first_name,
                last_name,
                false,
                'user',
                id,
                now(),
                now()
            FROM numbered
            SQL);
    }

    /**
     * Stop rather than leave two accounts answering to one name.
     *
     * The column carries no unique index - uniqueness is the application's rule - so a clash would
     * not fail, it would just quietly sit there until someone tried to sign in as one of the two.
     *
     * @return void
     * @throws \RuntimeException
     */
    private function failOnDuplicateUsernames(): void
    {
        $duplicates = $this->fetchAll(
            'SELECT username FROM users GROUP BY username HAVING count(*) > 1 ORDER BY username',
        );

        if ($duplicates) {
            throw new RuntimeException(
                'Creating a user for every dealer left these usernames held twice: '
                . implode(', ', array_column($duplicates, 'username')),
            );
        }
    }
}
