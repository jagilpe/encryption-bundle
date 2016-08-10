<?php

namespace EHEncryptionBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use EHEncryptionBundle\Crypt\KeyManagerInterface;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

class WebServicePrivateKeyLoadListener
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var \EHEncryptionBundle\Crypt\KeyManagerInterface
     */
    private $keyManager;

    public function __construct(
                    array $settings,
                    TokenStorageInterface $tokenStorage,
                    KeyManagerInterface $keyManager)
    {
        $this->settings = $settings;
        $this->tokenStorage = $tokenStorage;
        $this->keyManager = $keyManager;
    }

    /**
     * @param Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $user = $this->getUser();
        if ($user && $user instanceof PKEncryptionEnabledUserInterface) {
            $privateKey = $this->keyManager->getUserPrivateKey($user);
            $request->getSession()->set('pki_private_key', $privateKey);
        }
    }

    /**
     * Returns the logged in user
     *
     * @return \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        return $user;
    }
}