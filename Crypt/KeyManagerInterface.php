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
     * Returns the public key of the user
     *
     * @param mixed $user
     *
     * @return string
     */
    public function getPublicKey($user = null);

    /**
     * Returns the private key of the user
     *
     * @param mixed $user
     *
     * @return string
     */
    public function getPrivateKey($user = null);

    /**
     * Returns the key to be used to encrypt the entity
     *
     * @param mixed $entity
     */
    public function getEntityEncryptionKey($entity);

    /**
     * Returns the initialization vector to be used to encrypt/decrypt the entity
     *
     * @param mixed $entity
     */
    public function getEntityEncryptionIv($entity);
}