<?php

namespace Module7\EncryptionBundle\Entity\Traits;
use Module7\EncryptionBundle\Entity\EncryptableFile;

/**
 * Trait with the properties and methods needed by the encryptable file entities
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
trait EncryptableFileTrait
{
    /**
     * @var bool
     */
    protected $fileEncrypted = false;

    /**
     * Returns if the file associated with this entity is encrypted
     *
     * @return boolean
     */
    public function getFileEncrypted()
    {
        return $this->fileEncrypted;
    }
    /**
     * Sets the file associated with this entity as encrypted
     *
     * @param bool $encrypted
     * @return EncryptableFile
     */
    public function setFileEncrypted($encrypted)
    {
        $this->fileEncrypted = $encrypted;
        return $this;
    }

    /**
     * Checks if the file associated with this entity is encrypted
     *
     * @return boolean
     */
    public function isFileEncrypted()
    {
        return $this->getFileEncrypted();
    }
}