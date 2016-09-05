<?php

namespace EHEncryptionBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use EHEncryptionBundle\Crypt\KeyManagerInterface;
use EHEncryptionBundle\Crypt\KeyStoreInterface;
use EHEncryptionBundle\Crypt\KeyManager;
use PolavisConnectBundle\Security\SecurityCodeUser;
use EHEncryptionBundle\Exception\EncryptionException;

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

    /**
     * @var \EHEncryptionBundle\Crypt\KeyManagerInterface
     */
    private $keyManager;

    /**
     * @var \EHEncryptionBundle\Crypt\KeyStoreInterface
     */
    private $keyStore;

    public function __construct(
                    array $settings,
                    TokenStorageInterface $tokenStorage,
                    KeyManagerInterface $keyManager,
                    KeyStoreInterface $keyStore)
    {
        $this->settings = $settings;
        $this->tokenStorage = $tokenStorage;
        $this->keyManager = $keyManager;
        $this->keyStore = $keyStore;
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
                if (!$user instanceof SecurityCodeUser) {
                    $password = $request->request->get('_password');
                    $privateKey = $this->keyManager->getUserPrivateKey($user, array('password' => $password));
                    if ($privateKey) {
                        $request->getSession()->set(KeyManager::SESSION_PRIVATE_KEY_PARAM, $privateKey);
                    }
                    else {
                        throw new EncryptionException('Could not load user\'s key');
                    }
                }
                else {
                    $vivaUser = $user->getSecurityCode()->getUserProfile()->getUser();
                    $privateKey = $privateKey = $this->keyStore->getPrivateKey($vivaUser);
                    if ($privateKey) {
                        $request->getSession()->set(KeyManager::SESSION_PRIVATE_KEY_PARAM, $privateKey);
                    }
                    else {
                        throw new EncryptionException('Could not load user\'s key');
                    }
                }
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