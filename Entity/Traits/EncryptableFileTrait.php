<?php

namespace Module7\EncryptionBundle\Entity\Traits;

/**
 * Trait with the properties and methods needed by the encryptable file entities
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
trait EncryptableFileTrait
{
    protected $fileEncrypted = false;

    public function getFileEncrypted()
    {
        return $this->fileEncrypted;
    }

    public function setFileEncrypted($encrypted)
    {
        $this->fileEncrypted = $encrypted;
        return $this;
    }

    public function isFileEncrypted()
    {
        return $this->getFileEncrypted();
    }
}