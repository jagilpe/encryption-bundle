<?php

namespace EHEncryptionBundle\Crypt;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use EHEncryptionBundle\Exception\EncryptionException;
use EHEncryptionBundle\Event as EncryptionEvents;
use EHEncryptionBundle\Security\AccessCheckerInterface;

/**
 * Manages the different encryption keys
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class KeyManager implements KeyManagerInterface
{
    const SESSION_PRIVATE_KEY_PARAM = 'pki_private_key';

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @var \EHEncryptionBundle\Crypt\CryptographyProvider
     */
    private $cryptographyProvider;

    /**
     * @var \EHEncryptionBundle\Security\AccessCheckerInterface
     */
    private $accessChecker;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
                    TokenStorageInterface $tokenStorage,
                    SessionInterface $session,
                    CryptographyProvider $cryptographyProvider,
                    EventDispatcherInterface $dispatcher,
                    AccessCheckerInterface $accessChecker,
                    $settings)
    {
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->cryptographyProvider = $cryptographyProvider;
        $this->dispatcher = $dispatcher;
        $this->accessChecker = $accessChecker;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function generateUserPKIKeys(PKEncryptionEnabledUserInterface $user = null)
    {
        $oldKeys = null;

        if ($user->getPrivateKey() && $user->getPublicKey()) {
            $oldKeys = array(
                'private' => $user->getPrivateKey(),
                'public' => $user->getPublicKey(),
            );
        }

        // Dispatch the pre generation event
        $event = new EncryptionEvents\PKIKeyGenerationEvent($user, $oldKeys);
        $this->dispatcher->dispatch(EncryptionEvents\Events::PKI_KEY_PRE_GENERATE, $event);

        list($publicKey, $privateKey) = $this->generatePKIKeys();

        list($encryptedPrivateKey, $iv, $encrypted) = $this->encryptPrivateKey($privateKey, $user);

        $user->setPrivateKey($encryptedPrivateKey);
        if ($encrypted) {
            $user->setPrivateKeyIv($iv);
            $user->setPrivateKeyEncrypted(true);
        }
        $user->setPublicKey($publicKey);

        // Dispatch the post generation event
        $event = new EncryptionEvents\PKIKeyGenerationEvent($user, $oldKeys);
        $this->dispatcher->dispatch(EncryptionEvents\Events::PKI_KEY_POST_GENERATE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityEncryptionKeyData($entity)
    {
        $key = $this->getEntityEncryptionKey($entity);
        $iv = $this->getEntityEncryptionIv($entity);

        $keyData = new KeyData($key, $iv);

        return $keyData;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPublicKey(PKEncryptionEnabledUserInterface $user, array $params = array())
    {
        return $user->getPublicKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPrivateKey(PKEncryptionEnabledUserInterface $user, array $params = array())
    {
        if ($user->isPrivateKeyEncrypted()) {
            $privateKey = $this->decryptPrivateKey($user, $params);
        }
        else {
            $privateKey = $user->getPrivateKey();
        }

        return $privateKey;
    }

    /**
     * Returns the encryption key used to encrypt/decrpyt an entity
     *
     * @param mixed $entity
     *
     * @return string
     */
    private function getEntityEncryptionKey($entity)
    {
        $encryptedKey = $entity->getKey();

        if ($encryptedKey) {
            $key = $this->decryptSymmetricKey($encryptedKey);
        }
        else {
            $key = $this->generateSymmetricKey();

            // Insert the encrypted key in the entity
            $entity->setKey($this->encryptSymmetricKey($key, $entity));
        }

        return $key;
    }

    /**
     * Returns the initialization vector key used to encrypt/decrpyt an entity
     *
     * @param mixed $entity
     *
     * @return string
     */
    private function getEntityEncryptionIv($entity)
    {
        $iv = $entity->getIv();

        if (!$iv) {
            $iv = $this->cryptographyProvider->generateIV(CryptographyProviderInterface::PROPERTY_ENCRYPTION);
            $entity->setIv($iv);
        }

        return $iv;
    }

    /**
     * Returns the public key of the user
     *
     * @param mixed $user
     *
     * @return string
     */
    private function getPublicKey(PKEncryptionEnabledUserInterface $user = null)
    {
        return $user->getPublicKey();
    }

    /**
     * Returns the private key of the user logged in user
     *
     * @return string
     */
    private function getPrivateKey()
    {
        return $this->session->get('pki_private_key');
    }

    /**
     * Generates a symmetric key for the encryption of an Entity
     *
     * @return string
     */
    private function generateSymmetricKey()
    {
        return $this->cryptographyProvider->generateSecureKey();
    }

    private function encryptSymmetricKey($clearKey, $entity)
    {
        $users = $this->accessChecker->getAllowedUsers($entity);

        $encryptedKeys = array();

        foreach ($users as $user) {
            $publicKey = $this->getPublicKey($user);
            $encryptedKey = base64_encode($this->cryptographyProvider->encryptWithPublicKey($clearKey, $publicKey));
            $encryptedKeys[$user->getId()] = $encryptedKey;
        }

        return $encryptedKeys;
    }

    private function decryptSymmetricKey($encryptedKey)
    {
        $decryptedKey = null;
        $user = $this->getUser();

        if ($user && isset($encryptedKey[$user->getId()])) {
            $userKey = base64_decode($encryptedKey[$user->getId()]);
            $privateKey = $this->getPrivateKey();

            $decryptedKey = $this->cryptographyProvider->decryptWithPrivateKey($userKey, $privateKey);
        }

        return $decryptedKey;
    }

    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        return $user;
    }

    /**
     * Generates a pki keys pair
     *
     * @return array
     *
     * @throws EncryptionException
     */
    private function generatePKIKeys()
    {
        // OPENSSL config
        $config = array(
            'digest_alg' => $this->settings['private_key']['digest_method'],
            'private_key_bits' => $this->settings['private_key']['bits'],
            'private_key_type' => $this->settings['private_key']['type'],
        );

        $privateKey = null;
        $resource = openssl_pkey_new($config);

        openssl_pkey_export($resource, $privateKey);

        if(!$privateKey) {
            throw new EncryptionException('Private key could not be generated');
        }

        $publicKeyDetails = openssl_pkey_get_details($resource);
        $publicKey = $publicKeyDetails['key'];

        return array($publicKey, $privateKey);
    }

    /**
     * Encrypts the Private key of the user using his password
     *
     * @param string $privateKey
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     *
     * @return array
     */
    private function encryptPrivateKey($privateKey, PKEncryptionEnabledUserInterface $user)
    {
        $password = $user->getPlainPassword();
        $salt = $user->getSalt();

        if ($password) {
            $passwordDigest = $this->cryptographyProvider->getPasswordDigest($password, $salt);
            $iv = $this->cryptographyProvider->generateIV(CryptographyProviderInterface::PRIVATE_KEY_ENCRYPTION);

            $keyData = new KeyData($passwordDigest, $iv);
            try {
                $encryptedPrivateKey = $this->cryptographyProvider->encrypt(
                                $privateKey,
                                $keyData,
                                CryptographyProviderInterface::PRIVATE_KEY_ENCRYPTION);

                $encrypted = true;
            }
            catch (\Exception $ex) {
                $encryptedPrivateKey = $privateKey;
                $iv = null;
                $encrypted = false;
            }
        }
        else {
            $encryptedPrivateKey = $privateKey;
            $iv = null;
            $encrypted = false;
        }

        return array($encryptedPrivateKey, $iv, $encrypted);
    }

    /**
     * Encrypts the Private key of the user using his password or a digest of it
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @param array $params
     *
     * @return string|boolean
     */
    private function decryptPrivateKey(PKEncryptionEnabledUserInterface $user, array $params = array())
    {
        if (isset($params['password_digest'])) {
            $passwordDigest = base64_decode($params['password_digest']);
        }
        else {
            if (isset($params['password'])) {
                $salt = $user->getSalt();
                $passwordDigest = $this->cryptographyProvider->getPasswordDigest($params['password'], $salt);
            }
            else {
                throw new EncryptionException('Could not retrieve the user\'s key');
            }
        }

        $iv = $user->getPrivateKeyIv();
        $keyData = new KeyData($passwordDigest, $iv);
        $encryptedPrivateKey = $user->getPrivateKey();

        $privateKey = $this->cryptographyProvider->decrypt(
                        $encryptedPrivateKey,
                        $keyData,
                        CryptographyProviderInterface::PRIVATE_KEY_ENCRYPTION);

        return $privateKey;
    }
}