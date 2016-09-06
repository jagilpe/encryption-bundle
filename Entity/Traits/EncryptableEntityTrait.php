<?php

namespace EHEncryptionBundle\Entity\Traits;

trait EncryptableEntityTrait
{
    protected $encrypted = false;

    protected $migrated = false;

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

    public function setMigrated($migrated)
    {
        $this->migrated = $migrated;
        return $this;
    }

    public function isMigrated()
    {
        return $this->migrated;
    }
}