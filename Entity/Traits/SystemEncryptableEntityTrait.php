<?php

namespace Module7\EncryptionBundle\Entity\Traits;

use Doctrine\Common\Util\ClassUtils;

/**
 * Trait with the properties and methods needed by the encryptable entities for the
 * per user encryptable mode
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
trait SystemEncryptableEntityTrait
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