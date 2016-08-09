<?php

namespace EHEncryptionBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserPrivateKeyLoadListener
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(array $settings, TokenStorageInterface $tokenStorage)
    {
        $this->settings = $settings;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        // Check if this is one of the security_check routes
        $securityCheckRoutes = $this->settings['security_check_routes'];
        $route = $request->attributes->get('_route');
        if ($route && in_array($route, $securityCheckRoutes)) {
            $user = $this->getUser();

            if ($user) {
                // @TODO decrypt the key if applies
                $privateKey = $user->getPrivateKey();
                $request->getSession()->set('pki_private_key', $privateKey);
            }
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