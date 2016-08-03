<?php

namespace EHEncryptionBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait EncryptionEnabledUserTrait
{
    /**
     * @ORM\Column(name="_public_key", type="text", nullable = TRUE)
     */
    protected $publicKey;

    /**
     * @ORM\Column(name="_private_key", type="text", nullable = TRUE)
     */
    protected $privateKey;

    /**
     * {@inheritdoc}
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
        return $this;
    }
}