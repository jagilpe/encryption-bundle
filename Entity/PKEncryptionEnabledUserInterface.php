<?php

namespace Module7\EncryptionBundle\Entity;

/**
 * Defines the interface to treat with an encription enabled user entity
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface PKEncryptionEnabledUserInterface
{
    /**
     * Returns the Id of the User
     *
     * @return mixed
     */
    public function getId();

    /**
     * Returns the Public Key of the User
     *
     * @return string
     */
    public function getPublicKey();

    /**
     * Sets the Public Key of the User
     *
     * @param string $publicKey
     */
    public function setPublicKey($publicKey);

    /**
     * Returns the Private Key of the User
     *
     * @return string
     */
    public function getPrivateKey();

    /**
     * Sets the Private Key of the User
     *
     * @param string $privateKey
     */
    public function setPrivateKey($privateKey);

    /**
     * Checks if the private key is encrypted
     *
     * @return boolean
     */
    public function isPrivateKeyEncrypted();

    /**
     * Sets the encryption state of the private key of the user
     *
     * @param boolean $privateKey
     */
    public function setPrivateKeyEncrypted($privateKeyEncrypted);

    /**
     * Returns the initialization vector used to encrypt the private key of the user
     *
     * @return string
     */
    public function getPrivateKeyIv();

    /**
     * Sets the initialization vector used to encrypt the private key of the user
     *
     * @param string $privateKey
     */
    public function setPrivateKeyIv($privateKeyIv);
}