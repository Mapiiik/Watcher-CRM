<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Common audit fields shared by most entities.
 *
 * @property string $id Unique identifier of the entity
 *
 * @property \Cake\I18n\DateTime $created Timestamp of when the entity was created
 * @property string|null $created_by Identifier of the user who created the entity
 * @property \App\Model\Entity\AppUser|null $creator The user entity who created the record, if available
 *
 * @property \Cake\I18n\DateTime $modified Timestamp of when the entity was last modified
 * @property string|null $modified_by Identifier of the user who last modified the entity
 * @property \App\Model\Entity\AppUser|null $modifier The user entity who last modified the record, if available
 *
 * @property \Cake\I18n\DateTime|null $removed Timestamp of when the entity was removed (soft delete), if applicable
 * @property string|null $removed_by Identifier of the user who removed the entity, if applicable
 * @property \App\Model\Entity\AppUser|null $remover The user entity who removed the record, if applicable
 *
 * @property \Cake\I18n\DateTime|null $revoked Timestamp of when the entity was revoked, if applicable (for entities that can be revoked)
 * @property string|null $revoked_by Identifier of the user who revoked the entity, if applicable (for entities that can be revoked)
 * @property \App\Model\Entity\AppUser|null $revoker The user entity who revoked the record, if applicable (for entities that can be revoked)
 *
 * @property \Cake\I18n\DateTime|null $archived Timestamp of when the entity was archived, if applicable (for entities that can be archived)
 * @property string|null $archived_by Identifier of the user who archived the entity, if applicable (for entities that can be archived)
 * @property \App\Model\Entity\AppUser|null $archiver The user entity who archived the record, if applicable (for entities that can be archived)
 */
class AppEntity extends Entity
{
}
