<?php

namespace EHEncryptionBundle\Crypt;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use EHEncryptionBundle\Exception\EncryptionException;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use EHEncryptionBundle\Entity\PKIPrivateKey;

class KeyStore implements KeyStoreInterface
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var \EHEncryptionBundle\Crypt\CryptographyProviderInterface
     */
    private $cryptographyProvider;

    public function __construct(
                    Doctrine $doctrine,
                    CryptographyProviderInterface $cryptographyProvider)
    {
        $this->doctrine = $doctrine;
        $this->cryptographyProvider = $cryptographyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function addKeys(PKEncryptionEnabledUserInterface $user, $clearPrivateKey)
    {
        $userId = $user->getId();
        $userClass = get_class($user);

        if (!$userId) {
            throw new EncryptionException('Users must be persisted before storing his keys.');
        }

        // Remove the key if the user already has one
        $this->deleteUserKey($user, true);

        // Add the new key
        $key = new PKIPrivateKey();
        $key->setUserClass($userClass);
        $key->setUserId($userId);
        $key->setPrivateKey($clearPrivateKey);
        $key->setPublicKey($user->getPublicKey());
        $key->setEncrypted(false);

        $this->getEntityManager()->persist($key);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function removeKeys(PKEncryptionEnabledUserInterface $user)
    {
        $this->deleteUserKey($user, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivateKey(PKEncryptionEnabledUserInterface $user)
    {
        $key = $this->findKeyByUser($user);

        return $key->getPrivateKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKey(PKEncryptionEnabledUserInterface $user)
    {
        $key = $this->findKeyByUser($user);

        return $key->getPublicKey();
    }

    private function findKeyByUser(PKEncryptionEnabledUserInterface $user)
    {
        $userClass = $this->getUserClass($user);
        $userId = $user->getId();
        $key = $this->getKeyRepository()->findOneBy(array('userClass' => $userClass, 'userId' => $userId));

        return $key;
    }

    private function getUserClass(PKEncryptionEnabledUserInterface $user)
    {
        return \Doctrine\Common\Util\ClassUtils::getRealClass(get_class($user));
    }

    private function deleteUserKey(PKEncryptionEnabledUserInterface$user, $flush = false)
    {
        $key = $this->findKeyByUser($user);
        if ($key) {
            $this->getEntityManager()->remove($key);
            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getEntityManager()
    {
        return $this->doctrine->getManager();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getKeyRepository()
    {
        return $this->getEntityManager()->getRepository('EHEncryptionBundle:PKIPrivateKey');
    }
}