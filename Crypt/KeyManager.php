<?php

namespace EHEncryptionBundle\Crypt;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use EHEncryptionBundle\Crypt\CryptographyProvider;

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
     * @var unknown
     */
    private $cryptographyProvider;

    public function __construct(
                    TokenStorageInterface $tokenStorage,
                    CryptographyProvider $cryptographyProvider)
    {
        $this->tokenStorage = $tokenStorage;
        $this->cryptographyProvider = $cryptographyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKey($user = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey($user = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getEntityEncryptionKey($entity)
    {
        $key = $entity->getKey();

        if (!$key) {
            $key = base64_encode($this->cryptographyProvider->generateSecureKey());
            $entity->setKey($key);
        }

        return base64_decode($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityEncryptionIv($entity)
    {
        $iv = $entity->getIv();

        if (!$iv) {
            $iv = base64_encode($this->cryptographyProvider->generateIV(CryptographyProviderInterface::PROPERTY_ENCRYPTION));
            $entity->setIv($iv);
        }

        return base64_decode($iv);
    }
}