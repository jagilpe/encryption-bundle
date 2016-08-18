<?php

namespace EHEncryptionBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

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
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function canUseOwnerPrivateKey($entity, UserInterface $user)
    {

    }
}