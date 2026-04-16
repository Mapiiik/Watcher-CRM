<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Common audit fields shared by most entities.
 *
 * @property string $id Unique identifier of the entity
 * @property \Cake\I18n\DateTime $created Timestamp of when the entity was created
 * @property string|null $created_by Identifier of the user who created the entity
 * @property \App\Model\Entity\AppUser|null $creator The user entity who created the record, if available
 * @property \Cake\I18n\DateTime $modified Timestamp of when the entity was last modified
 * @property string|null $modified_by Identifier of the user who last modified the entity
 * @property \App\Model\Entity\AppUser|null $modifier The user entity who last modified the record, if available
 */
class AppEntity extends Entity
{
}
