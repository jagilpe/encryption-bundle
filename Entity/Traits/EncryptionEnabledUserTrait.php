<?php

namespace EHEncryptionBundle\Entity\Traits;

trait EncryptionEnabledUserTrait
{
    protected $publicKey;

    protected $privateKey;

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
        return $this;
    }
}