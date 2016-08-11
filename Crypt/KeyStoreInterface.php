<?php

namespace EHEncryptionBundle\Crypt;

use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

interface KeyStoreInterface
{
    /**
     * Adds the private key that corresponds with a user
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @param string $clearPrivateKey
     */
    public function addKeys(PKEncryptionEnabledUserInterface $user, $clearPrivateKey);

    /**
     * Removes the private key of a user
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function removeKeys(PKEncryptionEnabledUserInterface $user);

    /**
     * Retrieves the private key that corresponds with a user
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function getPrivateKey(PKEncryptionEnabledUserInterface $user);

    /**
     * Retrieves the public key that corresponds with a user
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function getPublicKey(PKEncryptionEnabledUserInterface $user);
}