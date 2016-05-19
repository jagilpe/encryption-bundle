<?php

namespace EHEncryptionBundle\Crypt;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Manages the different encryption keys
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class KeyManager implements KeyManagerInterface
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getPublicKey(User $user = null)
    {

    }

    public function getPrivateKey(User $user = null)
    {

    }
}