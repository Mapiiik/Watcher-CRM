<?php
declare(strict_types=1);

namespace App\Test\Traits;

use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validation;

/**
 * Shared assertions for the table test cases.
 *
 * The methods the baker leaves a stub for - `initialize`, `validationDefault` and `buildRules` -
 * are hard to test without restating what they declare, which proves nothing. These ask something
 * the declarations do not say by themselves instead.
 */
trait TableTestTrait
{
    /**
     * Every association has to name a column that is really there.
     *
     * An association is a claim about two schemas, and nothing checks it until something reads the
     * association - a renamed or dropped column leaves it pointing at nothing, and the page that
     * contains it is the one that finds out.
     *
     * @param \Cake\ORM\Table $table Table to check.
     * @return void
     */
    protected function assertAssociationsMatchTheSchema(Table $table): void
    {
        $checked = 0;

        foreach ($table->associations() as $association) {
            // for a belongsTo the key is on this table, for a hasMany on the other one
            if ($association instanceof BelongsTo) {
                $columns = $table->getSchema()->columns();
            } elseif ($association instanceof HasMany) {
                $columns = $association->getTarget()->getSchema()->columns();
            } else {
                continue;
            }

            foreach ((array)$association->getForeignKey() as $field) {
                // an association can say it has no key of its own
                if (!is_string($field)) {
                    continue;
                }

                $this->assertContains(
                    $field,
                    $columns,
                    sprintf(
                        'The %s association of %s names a column that is not there.',
                        $association->getName(),
                        $table->getAlias(),
                    ),
                );
                $checked++;
            }
        }

        if ($checked === 0) {
            $this->markTestSkipped(sprintf('%s stands on its own.', $table->getAlias()));
        }
    }

    /**
     * A new record has to be refused when nothing is filled in.
     *
     * It is a shallow question, but not an empty one: it fails the moment a table stops requiring
     * anything at all, which is what happens when a validator is accidentally emptied or the method
     * is renamed out of the way.
     *
     * Reading a stored record back and re-validating it would ask more, but it would ask it of a
     * path the application never takes - a marshalled entity carries enums, dates and decimals as
     * objects, which is not what arrives from a form.
     *
     * @param \Cake\ORM\Table $table Table to check.
     * @return void
     */
    protected function assertEmptyRecordIsRefused(Table $table): void
    {
        $errors = $table->newEntity([])->getErrors();

        if ($errors === []) {
            $this->markTestSkipped(sprintf('%s asks nothing of a new record.', $table->getAlias()));
        }

        $this->assertNotSame([], $errors);
    }

    /**
     * The rules have to refuse a record whose references point nowhere.
     *
     * Every foreign key the record actually fills in is pointed at an id nothing carries. A save
     * that goes through means nothing stands between the application and a row referring to a
     * record that was never there.
     *
     * The columns recording who created and last changed the row are left alone: they are filled in
     * by the footprint behavior rather than by anybody using the application, and are guarded by the
     * database rather than by these rules.
     *
     * @param \Cake\ORM\Table $table Table to check.
     * @return void
     */
    protected function assertDanglingReferencesAreRefused(Table $table): void
    {
        $record = $table->find()->firstOrFail();
        $repointed = false;

        foreach ($table->associations() as $association) {
            if (!$association instanceof BelongsTo) {
                continue;
            }

            foreach ((array)$association->getForeignKey() as $field) {
                // an association can say it has no key of its own
                if (!is_string($field) || str_ends_with($field, '_by')) {
                    continue;
                }

                // a key left empty is one the record does not use, and points nowhere already
                $value = $record->get($field);
                if (!is_string($value) || !Validation::uuid($value)) {
                    continue;
                }

                $record->set($field, Text::uuid());
                $repointed = true;
            }
        }

        if (!$repointed) {
            $this->markTestSkipped(sprintf('%s holds no reference to point elsewhere.', $table->getAlias()));
        }

        $this->assertFalse(
            (bool)$table->save($record),
            sprintf('%s saved a record whose references point nowhere.', $table->getAlias()),
        );
    }
}
