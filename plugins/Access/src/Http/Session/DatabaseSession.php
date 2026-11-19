<?php
declare(strict_types=1);

namespace Access\Http\Session;

use Cake\Http\Session\DatabaseSession as CakeDatabaseSession;
use Override;
use PDOException;

/**
 * Sessions kept in the database, written safely by requests that come at the same time.
 *
 * CakePHP looks for the row of a session and then inserts or updates it. Two requests of a session
 * that has no row yet, such as one that has just expired, both find none, and the later insert
 * fails on the primary key. That request writes again, and then updates the row the other made.
 */
class DatabaseSession extends CakeDatabaseSession
{
    /**
     * SQLSTATE of a duplicate key.
     */
    public const UNIQUE_VIOLATION = '23505';

    /**
     * @inheritDoc
     */
    #[Override]
    public function write(string $id, string $data): bool
    {
        try {
            return parent::write($id, $data);
        } catch (PDOException $e) {
            if ((string)$e->getCode() !== self::UNIQUE_VIOLATION) {
                throw $e;
            }

            return parent::write($id, $data);
        }
    }
}
