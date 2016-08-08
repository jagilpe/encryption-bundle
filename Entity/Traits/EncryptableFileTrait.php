<?php

namespace EHEncryptionBundle\Entity\Traits;

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