<?php

namespace Jagilpe\EncryptionBundle\Entity;
use Jagilpe\EncryptionBundle\Crypt\SymmetricKey;

/**
 * Contract for all the encryptable entities using per user encryption
 *
 * @package Jagilpe\EncryptionBundle\Entity
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface PerUserEncryptableEntity extends EncryptableEntity
{
    /**
     * Returns the key used to encrypt this entity
     *
     * @return SymmetricKey
     */
    public function getKey();

    /**
     * Sets the key used to encrypt this entity
     *
     * @param SymmetricKey $key
     */
    public function setKey(SymmetricKey $key);

    /**
     * Returns the initialization vector used to encrypt this entity
     *
     * @return string
     */
    public function getIv();

    /**
     * Sets the initialization vector used to encrypt this entity
     *
     * @param string $iv
     */
    public function setIv($iv);

    /**
     * Returns the owner of this entity
     *
     * @return PKEncryptionEnabledUserInterface
     */
    public function getOwnerUser();
}