<?php

namespace EHEncryptionBundle\Crypt;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

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
                    CryptographyProvider $cryptographyProvider,
                    EventDispatcherInterface $dispatcher,
                    AccessCheckerInterface $accessChecker,
                    $settings)
    {
        $this->tokenStorage = $tokenStorage;
        $this->cryptographyProvider = $cryptographyProvider;
        $this->dispatcher = $dispatcher;
        $this->accessChecker = $accessChecker;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function generateUserPKIKeys($user = null)
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

        $user->setPrivateKey($privateKey);
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
            $iv = base64_encode($this->cryptographyProvider->generateIV(CryptographyProviderInterface::PROPERTY_ENCRYPTION));
            $entity->setIv($iv);
        }

        return base64_decode($iv);
    }

    /**
     * Returns the public key of the user
     *
     * @param mixed $user
     *
     * @return string
     */
    private function getPublicKey($user = null)
    {
        return $user->getPublicKey();
    }

    /**
     * Returns the private key of the user
     *
     * @param mixed $user
     *
     * @return string
     */
    private function getPrivateKey($user = null)
    {
        return $user->getPrivateKey();
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

        if (isset($encryptedKey[$user->getId()])) {
            $userKey = base64_decode($encryptedKey[$user->getId()]);
            $privateKey = $this->getPrivateKey($user);

            $decryptedKey = $this->cryptographyProvider->decryptWithPrivateKey($userKey, $privateKey);
        }

        return $decryptedKey;
    }

    private function getUser()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return $user;
    }

}