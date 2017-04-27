<?php

namespace Jagilpe\EncryptionBundle\Crypt;

use Jagilpe\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

/**
 * Holds the symmetric key used to encrypt the fields of an entity
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class SymmetricKey
{
    const SYSTEM_IDENTIFIER = '_system';

    /**
     * The symmetric key encrypted with all the public keys of the
     * users with permissions to access the entity
     *
     * @var array
     */
    protected $encryptedKeys = array();

    /**
     * Adds a new version of the key encrypted with the master key of the system
     *
     * @param string $encryptedKey
     */
    public function addSystemKey($encryptedKey)
    {
        $this->encryptedKeys[self::SYSTEM_IDENTIFIER] = $encryptedKey;
    }

    /**
     * Returns the encrypted key that corresponds to the system master key
     *
     * @return NULL|string
     */
    public function getSystemKey()
    {
        return isset($this->encryptedKeys[self::SYSTEM_IDENTIFIER])
            ? $this->encryptedKeys[self::SYSTEM_IDENTIFIER]
            : null;
    }

    /**
     * Adds a new version of the key encrypted with the key of a new user
     *
     * @param \Jagilpe\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @param string $encryptedKey
     */
    public function addKey(PKEncryptionEnabledUserInterface $user, $encryptedKey)
    {
        $userClass = $this->getUserClass($user);
        $userId = $user->getId();
        if (!isset($this->encryptedKeys[$userClass])) {
            $this->encryptedKeys[$userClass] = array();
        }
        $this->encryptedKeys[$userClass][$userId] = $encryptedKey;
    }

    /**
     * Returns the encrypted key that corresponds to a determined user
     *
     * @param PKEncryptionEnabledUserInterface $user
     * @return NULL|string
     */
    public function getKey(PKEncryptionEnabledUserInterface $user)
    {
        $userClass = $this->getUserClass($user);
        $userId = $user->getId();

        return isset($this->encryptedKeys[$userClass]) && isset($this->encryptedKeys[$userClass][$userId])
            ? $this->encryptedKeys[$userClass][$userId]
            : null;
    }

    /**
     * Sets the user id for the key that were not identified when persisted.
     * There are some cases that a key can be persisted without the user id.
     * (All entities that are persisted at the same time as the user)
     *
     * @param string $userClass
     */
    public function updateUnidentifiedKey($user)
    {
        $userClass = $this->getUserClass($user);
        if (isset($this->encryptedKeys[$userClass]) && isset($this->encryptedKeys[$userClass][""])) {
            $this->encryptedKeys[$userClass][$user->getId()] = $this->encryptedKeys[$userClass][""];
            unset($this->encryptedKeys[$userClass][""]);
        }
    }

    private function getUserClass(PKEncryptionEnabledUserInterface $user)
    {
        return \Doctrine\Common\Util\ClassUtils::getRealClass(get_class($user));
    }
}