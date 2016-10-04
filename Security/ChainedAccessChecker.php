<?php

namespace Module7\EncryptionBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Module7\EncryptionBundle\Exception\EncryptionException;

/**
 * Implementation of the AccessCheckerInterface that chains the response of a list of othe AccessCheckers
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class ChainedAccessChecker implements AccessCheckerInterface
{
    /**
     * The Encryption Bundle Settings
     *
     * @var array
     */
    private $settings;

    /**
     * The Access Checkers configured to check the permission to access the decryption
     *
     * @var array<AccessCheckerInterface>
     */
    private $accessCheckers;

    public function __construct(array $accessCheckers, array $settings)
    {
        if (empty($accessCheckers)) {
            throw new EncryptionException('At least one AccessChecker must be provider to be chained');
        }

        foreach ($accessCheckers as $accessChecker) {
            if (!($accessChecker instanceof AccessCheckerInterface)) {
                throw new EncryptionException('The access checkers must implement the AccessCheckerInterface interface');
            }
        }
        $this->accessCheckers = $accessCheckers;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedUsers($entity)
    {
        $users = array();
        foreach ($this->accessCheckers as $accessChecker) {
            $users = array_merge($users, $accessChecker->getAllowedUsers($entity));
        }
        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function canUseOwnerPrivateKey($entity, $user = null)
    {
        $canAccess = false;
        foreach ($this->accessCheckers as $accessChecker) {
            if ($accessChecker->canUseOwnerPrivateKey($entity, $user)) {
                $canAccess = true;
                break;
            }
        }
        return $canAccess;
    }

    private function getUser($entity)
    {
        return $entity->getUser();
    }
}