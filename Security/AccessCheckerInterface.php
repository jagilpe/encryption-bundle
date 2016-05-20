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
}