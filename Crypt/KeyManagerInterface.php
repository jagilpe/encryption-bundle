<?php

namespace EHEncryptionBundle\Crypt;

use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

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
     * @param mixed $user
     */
    public function generateUserPKIKeys(PKEncryptionEnabledUserInterface $user = null);

    /**
     * Returns the key to be used to encrypt the entity
     *
     * @param mixed $entity
     * @param array $params
     *
     * @return \EHEncryptionBundle\Crypt\KeyDataInterface
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