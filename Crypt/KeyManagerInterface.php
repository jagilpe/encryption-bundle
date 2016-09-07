<?php

namespace Module7\EncryptionBundle\Crypt;

use Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

/**
 * Manages the different encryption keys
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface KeyManagerInterface
{
    /**
     * Generates the required keys for the PKI
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function generateUserPKIKeys(PKEncryptionEnabledUserInterface $user);

    /**
     * Stores the keys of the user in the configured key store
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function storeUserPKIKeys(PKEncryptionEnabledUserInterface $user);

    /**
     * Handles a password change by the user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @param $currentPassword
     *
     */
    public function handleUserPasswordChange(PKEncryptionEnabledUserInterface $user, $currentPassword);

    /**
     * Handles a password reset by the user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     *
     */
    public function handleUserPasswordReset(PKEncryptionEnabledUserInterface $user);

    /**
     * Returns the key to be used to encrypt the entity
     *
     * @param mixed $entity
     * @param array $params
     *
     * @return \Module7\EncryptionBundle\Crypt\KeyDataInterface
     */
    public function getEntityEncryptionKeyData($entity);

    /**
     * Returns the public key of the given user
     *
     * @param PKEncryptionEnabledUserInterface $user
     *
     * @return string
     */
    public function getUserPublicKey(PKEncryptionEnabledUserInterface $user, array $params = array());

    /**
     * Returns the private key of the given user
     *
     * @param PKEncryptionEnabledUserInterface $user
     *
     * @return string
     */
    public function getUserPrivateKey(PKEncryptionEnabledUserInterface $user, array $params = array());
}