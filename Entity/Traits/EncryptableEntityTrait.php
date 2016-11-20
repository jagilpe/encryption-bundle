<?php

namespace Module7\EncryptionBundle\Entity\Traits;
use Module7\EncryptionBundle\Entity\EncryptableEntity;

/**
 * Trait with the properties and methods needed by the encryptable entities
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
trait EncryptableEntityTrait
{
    /**
     * @var bool
     */
    protected $encrypted = false;

    /**
     * @var bool
     */
    protected $migrated = false;

    /**
     * Returns if the entity is already encrypted
     *
     * @return bool
     */
    public function getEncrypted()
    {
        return $this->encrypted;
    }

    /**
     * Sets the entity as already encrypted
     *
     * @param bool $encrypted
     * @return EncryptableEntity
     */
    public function setEncrypted($encrypted)
    {
        $this->encrypted = $encrypted;
        return $this;
    }

    /**
     * Checks if the entity is already encrypted
     *
     * @return bool
     */
    public function isEncrypted()
    {
        return $this->getEncrypted();
    }

    /**
     * Sets the entity as already migrated
     *
     * @param bool $migrated
     * @return EncryptableEntity
     */
    public function setMigrated($migrated)
    {
        $this->migrated = $migrated;
        return $this;
    }

    /**
     * Checks if the entity encryption has already been migrated for this entity
     *
     * @return bool
     */
    public function isMigrated()
    {
        return $this->migrated;
    }
}