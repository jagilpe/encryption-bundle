<?php

namespace EHEncryptionBundle\Security;

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
     * @param mixed $user
     *
     * return @boolean
     */
    public function canUseOwnerPrivateKey($entity, $user);
}