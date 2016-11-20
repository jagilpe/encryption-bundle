<?php

namespace Module7\EncryptionBundle\Crypt;

use Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

/**
 * Defines an interface to work the uses´ key store
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface KeyStoreInterface
{
    /**
     * Adds the private key that corresponds with a user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @param string $clearPrivateKey
     */
    public function addKeys(PKEncryptionEnabledUserInterface $user, $clearPrivateKey);

    /**
     * Removes the private key of a user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function removeKeys(PKEncryptionEnabledUserInterface $user);

    /**
     * Retrieves the private key that corresponds with a user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function getPrivateKey(PKEncryptionEnabledUserInterface $user);

    /**
     * Retrieves the public key that corresponds with a user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function getPublicKey(PKEncryptionEnabledUserInterface $user);

    /**
     * Returns the public master key
     *
     * @return string
     */
    public function getPublicMasterKey();

    /**
     * Returns the private master key
     *
     * @return string
     */
    public function getPrivateMasterKey();
}