<?php

namespace Module7\EncryptionBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait with the properties and methods needed by the encryption enabled user entity
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
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
     * @ORM\Column(name="_private_key_encrypted", type="boolean", nullable = TRUE)
     */
    protected $privateKeyEncrypted = false;

    /**
     * @ORM\Column(name="_private_key_iv", type="text", nullable = TRUE)
     */
    protected $privateKeyIv;

    /**
     *
     * @var string
     */
    protected $passwordDigest;

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

    /**
     * {@inheritdoc}
     */
    public function getPrivateKeyEncrypted()
    {
        return $this->privateKeyEncrypted;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrivateKeyEncrypted($privateKeyEncrypted)
    {
        $this->privateKeyEncrypted = $privateKeyEncrypted;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isPrivateKeyEncrypted()
    {
        return $this->getPrivateKeyEncrypted();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKeyIv()
    {
        return $this->privateKeyIv;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrivateKeyIv($privateKeyIv)
    {
        $this->privateKeyIv = $privateKeyIv;
        return $this;
    }

    public function getPasswordDigest()
    {
        return $this->passwordDigest;
    }

    public function setPasswordDigest($passwordDigest)
    {
        $this->passwordDigest = $passwordDigest;
        return $this;
    }
}