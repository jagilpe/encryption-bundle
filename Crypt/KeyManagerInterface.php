<?php

namespace EHEncryptionBundle\Crypt;

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
    public function generateUserPKIKeys($user = null);

    /**
     * Returns the key to be used to encrypt the entity
     *
     * @param mixed $entity
     * @param array $params
     *
     * @return \EHEncryptionBundle\Crypt\KeyDataInterface
     */
    public function getEntityEncryptionKeyData($entity);
}