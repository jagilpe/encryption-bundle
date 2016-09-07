<?php

namespace EHEncryptionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Private key entity for the default implementation of the Key Store
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="_pki_user_keys")
 */
class PKIPrivateKey
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="user_class", type="string", length=100, nullable=false)
     */
    protected $userClass;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="public_key", type="text", nullable=false)
     */
    protected $publicKey;

    /**
     * @var string
     *
     * @ORM\Column(name="private_key", type="text", nullable=false)
     */
    protected $privateKey;

    /**
     * @var boolean
     *
     * @ORM\Column(name="encrypted", type="boolean", nullable=false)
     */
    protected $encrypted = false;

    /**
     * @var string
     *
     * @ORM\Column(name="_key", type="text", nullable=true)
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(name="_iv", type="text", nullable=true)
     */
    protected $iv;

    public function getUserClass()
    {
        return $this->userClass;
    }

    public function setUserClass($userClass)
    {
        $this->userClass = $userClass;
        return $this;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

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