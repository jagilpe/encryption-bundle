<?php

namespace Module7\EncryptionBundle\Entity\Traits;

use Doctrine\Common\Util\ClassUtils;
use Module7\EncryptionBundle\Crypt\SymmetricKey;
use Module7\EncryptionBundle\Entity\PerUserEncryptableEntity;

/**
 * Trait with the properties and methods needed by the encryptable entities for the
 * per user encryptable mode
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
trait PerUserEncryptableEntityTrait
{
    use EncryptableEntityTrait;

    /**
     * @var SymmetricKey
     */
    protected $key;

    /**
     * @var string
     */
    protected $iv;

    /**
     * Returns the key used to encrypt this entity
     *
     * @return SymmetricKey
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets the key used to encrypt this entity
     *
     * @param SymmetricKey $key
     * @return PerUserEncryptableEntity
     */
    public function setKey(SymmetricKey $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Returns the initialization vector used to encrypt this entity
     *
     * @return string
     */
    public function getIv()
    {
        return $this->iv;
    }

    /**
     * Sets the initialization vector used to encrypt this entity
     *
     * @param string $iv
     * @return PerUserEncryptableEntity
     */
    public function setIv($iv)
    {
        $this->iv = $iv;
        return $this;
    }

    /**
     * Returns the owner of this entity
     *
     * @return PKEncryptionEnabledUserInterface
     */
    public function getOwnerUser()
    {
        $reflection = ClassUtils::newReflectionObject($this);

        if ($reflection->hasMethod('getUser')) {
            return $this->getUser();
        }
        elseif ($reflection->hasMethod('getUserProfile')) {
            return $this->getUserProfile() ? $this->getUserProfile()->getUser() : null;
        }

        return null;
    }
}