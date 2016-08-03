<?php

namespace EHEncryptionBundle\Entity;

interface PKEncryptionEnabledUserInterface
{
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
}