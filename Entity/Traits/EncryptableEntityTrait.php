<?php

namespace EHEncryptionBundle\Entity\Traits;

trait EncryptableEntityTrait
{
    protected $encrypted;

    public function getEncrypted()
    {
        return $this->encrypted;
    }

    public function setEncrypted($encrypted)
    {
        $this->encrypted = $encrypted;
        return $this;
    }

    public function isEncrypted()
    {
        return $this->getEncrypted();
    }
}