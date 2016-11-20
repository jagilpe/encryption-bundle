<?php

namespace Module7\EncryptionBundle\EventListener;

use Module7\EncryptionBundle\Service\EncryptionService;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Module7\EncryptionBundle\Crypt\KeyManagerInterface;
use Module7\EncryptionBundle\Crypt\KeyStoreInterface;
use Module7\EncryptionBundle\Crypt\KeyManager;
use PolavisConnectBundle\Security\SecurityCodeUser;
use Module7\EncryptionBundle\Exception\EncryptionException;

/**
 * Event listener to load the private key of the logged in user
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 */
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
     * @var \Module7\EncryptionBundle\Crypt\KeyManagerInterface
     */
    private $keyManager;

    /**
     * @var \Module7\EncryptionBundle\Crypt\KeyStoreInterface
     */
    private $keyStore;

    /**
     * @var EncryptionService
     */
    private $encryptionService;

    public function __construct(
        array $settings,
        TokenStorageInterface $tokenStorage,
        KeyManagerInterface $keyManager,
        KeyStoreInterface $keyStore,
        EncryptionService $encryptionService)
    {
        $this->settings = $settings;
        $this->tokenStorage = $tokenStorage;
        $this->keyManager = $keyManager;
        $this->keyStore = $keyStore;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @throws EncryptionException
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->encryptionService->isPerUserEncryptionEnabled()) {
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
    }

    /**
     * Returns the logged in user
     *
     * @return \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        return $user;
    }
}