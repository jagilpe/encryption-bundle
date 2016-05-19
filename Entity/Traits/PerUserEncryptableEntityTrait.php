<?php

namespace EHEncryptionBundle\Entity\Traits;

trait PerUserEncryptableEntityTrait
{
    use EncryptableEntityTrait;

    protected $key;

    protected $iv;

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    public function getIv()
    {
        return $this->iv;
    }

    public function setIv($iv)
    {
        $this->iv = $iv;
        return $this;
    }
}