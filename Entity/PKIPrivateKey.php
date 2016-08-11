<?php

namespace EHEncryptionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
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
}