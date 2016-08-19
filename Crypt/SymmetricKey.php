<?php

namespace EHEncryptionBundle\Crypt;

use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

/**
 * Holds the symmetric key used to encrypt the fields of an entity
 */
class SymmetricKey
{
    /**
     * The symmetric key encrypted with all the public keys of the
     * users with permissions to access the entity
     *
     * @var array
     */
    protected $encryptedKeys = array();

    public function addKey(PKEncryptionEnabledUserInterface $user, $encryptedKey)
    {
        $userClass = $this->getUserClass($user);
        $userId = $user->getId();
        if (!isset($this->encryptedKeys[$userClass])) {
            $this->encryptedKeys[$userClass] = array();
        }
        $this->encryptedKeys[$userClass][$userId] = $encryptedKey;
    }

    public function getKey(PKEncryptionEnabledUserInterface $user)
    {
        $userClass = $this->getUserClass($user);
        $userId = $user->getId();

        return isset($this->encryptedKeys[$userClass]) && isset($this->encryptedKeys[$userClass][$userId])
            ? $this->encryptedKeys[$userClass][$userId]
            : null;
    }

    private function getUserClass(PKEncryptionEnabledUserInterface $user)
    {
        return \Doctrine\Common\Util\ClassUtils::getRealClass(get_class($user));
    }
}