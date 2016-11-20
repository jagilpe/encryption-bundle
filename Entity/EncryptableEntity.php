<?php

namespace Module7\EncryptionBundle\Entity;

/**
 * Contract for all the encryptable entities
 *
 * @package Module7\EncryptionBundle\Entity
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface EncryptableEntity
{
    /**
     * Checks if the entity is already encrypted
     *
     * @return boolean
     */
    public function isEncrypted();

    /**
     * Checks if the entity encryption has already been migrated for this entity
     *
     * @return boolean
     */
    public function isMigrated();
}