<?php

namespace Module7\EncryptionBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Defines the interface to check the different permissions of the users related with the encryption
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface AccessCheckerInterface
{
    /**
     * Returns the list of users that should be able to decrypt
     * the content of the entity
     *
     * @param mixed $entity
     *
     * @return array
     */
    public function getAllowedUsers($entity);

    /**
     * Checks if the user is allowed to decrypt the data of the entity
     * using the private key of its owner
     *
     * @param mixed $entity
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * return @boolean
     */
    public function canUseOwnerPrivateKey($entity, UserInterface $user = null);
}