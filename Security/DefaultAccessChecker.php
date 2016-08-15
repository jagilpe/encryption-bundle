<?php

namespace EHEncryptionBundle\Security;

use PolavisConnectBundle\Security\SecurityCodeUser;

class DefaultAccessChecker implements AccessCheckerInterface
{
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedUsers($entity)
    {
        return array($this->getUser($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function canUseVivaUserPrivateKey($entity, $user)
    {
        return $user instanceof SecurityCodeUser;
    }

    private function getUser($entity)
    {
        return $entity->getUser();
    }
}