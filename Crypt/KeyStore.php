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

    /**
     * @var array
     */
    private $masterKeyData;

    /**
     * @var resource
     */
    private $masterKey;

    /**
     * @var string
     */
    private $publicMasterKey;

    /**
     * @var string
     */
    private $privateMasterKey;

    public function __construct(
                    Doctrine $doctrine,
                    CryptographyProviderInterface $cryptographyProvider,
                    array $masterKeyData)
    {
        $this->doctrine = $doctrine;
        $this->cryptographyProvider = $cryptographyProvider;
        $this->masterKeyData = $masterKeyData;
    }

    /**
     * {@inheritdoc}
     */
    public function addKeys(PKEncryptionEnabledUserInterface $user, $clearPrivateKey)
    {
        $userId = $user->getId();

        if (!$userId) {
            throw new EncryptionException('Users must be persisted before storing his keys.');
        }

        // Reset the key data
        $pkiKey = $this->getUserKey($user);
        $pkiKey->setPrivateKey($clearPrivateKey);
        $pkiKey->setPublicKey($user->getPublicKey());
        $pkiKey->setEncrypted(false);

        // Encrypt the private key
        $this->encryptPrivateKey($pkiKey);

        // Persist the key
        $this->getEntityManager()->persist($pkiKey);
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

        if ($key->isEncrypted()) {
            return $this->decryptPrivateKey($key);
        }
        else {
            return $key->getPrivateKey();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKey(PKEncryptionEnabledUserInterface $user)
    {
        $key = $this->findKeyByUser($user);

        return $key->getPublicKey();
    }

    /**
     * Returns the Key pair of the user or a new one if it has not already been persisted
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     *
     * @return \EHEncryptionBundle\Entity\PKIPrivateKey
     */
    private function getUserKey(PKEncryptionEnabledUserInterface $user)
    {
        $pkiKey = $this->findKeyByUser($user);

        if (!$pkiKey) {
            $userId = $user->getId();
            $userClass = $this->getUserClass($user);

            $pkiKey = new PKIPrivateKey();
            $pkiKey->setUserClass($userClass);
            $pkiKey->setUserId($userId);
        }

        return $pkiKey;
    }

    /**
     * Returns the previously persisted key pair of a user
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @return \EHEncryptionBundle\Entity\PKIPrivateKey
     */
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

    /**
     * Deletes the key pair of a user
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
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

    /**
     * Encrypts the private key of the user using the app master public key
     *
     * @param \EHEncryptionBundle\Entity\PKIPrivateKey $pkiKey
     */
    private function encryptPrivateKey(PKIPrivateKey $pkiKey)
    {
        // Generate the symmetric key and iv to encrypt the users' key
        $iv = $this->cryptographyProvider->generateIV(CryptographyProviderInterface::PRIVATE_KEY_ENCRYPTION);
        $symmetricKey = $this->cryptographyProvider->generateSecureKey();
        $keyData = new KeyData($symmetricKey, $iv);

        // The private key of the user that we want to encrypt
        $userPrivateKey = $pkiKey->getPrivateKey();
        $encryptedUserPrivateKey = $this->cryptographyProvider->encrypt($userPrivateKey, $keyData);

        if ($encryptedUserPrivateKey) {
            $pkiKey->setPrivateKey($encryptedUserPrivateKey);
            $pkiKey->setEncrypted(true);

            // Now we have to encrypt the symmetric key with the public master key
            $publicMasterKey = $this->getPublicMasterKey();
            $encryptedKey = base64_encode($this->cryptographyProvider->encryptWithPublicKey($symmetricKey, $publicMasterKey));
            $pkiKey->setKey($encryptedKey);
            $pkiKey->setIv($iv);
        }
        else {
            $pkiKey->setKey(null);
            $pkiKey->setIv(null);
            $pkiKey->setEncrypted(false);
        }
    }

    /**
     * Descrypts the private key of the user using the app master private key and returns it
     *
     * @param \EHEncryptionBundle\Entity\PKIPrivateKey $pkiKey
     *
     * @return string
     */
    private function decryptPrivateKey(PKIPrivateKey $pkiKey)
    {
        // Get the symmetric key used to encrypt the private key of the user
        $encryptedKey = base64_decode($pkiKey->getKey());

        $privateMasterKey = $this->getPrivateMasterKey();
        $symmetricKey = $this->cryptographyProvider->decryptWithPrivateKey($encryptedKey, $privateMasterKey);

        if ($symmetricKey) {
            // Decrypt the private key of the user
            $iv = $pkiKey->getIv();
            $keyData = new KeyData($symmetricKey, $iv);

            $encryptedUserPrivateKey = $pkiKey->getPrivateKey();
            $userPrivateKey = $this->cryptographyProvider->decrypt($encryptedUserPrivateKey, $keyData);

            return $userPrivateKey;
        }
        else {
            throw new EncryptionException('Could not retrieve the encryption keys of the user');
        }
    }

    /**
     * Returns the public master key
     *
     * @return string
     */
    private function getPublicMasterKey()
    {
        if (!$this->publicMasterKey) {
            $masterKey = $this->getMasterKey();
            $publicKeyDetails = openssl_pkey_get_details($masterKey);
            $this->publicMasterKey = $publicKeyDetails['key'];
        }
        return $this->publicMasterKey;
    }

    /**
     * Returns the private master key
     *
     * @return string
     */
    private function getPrivateMasterKey()
    {
        if (!$this->privateMasterKey) {
            $masterKey = $this->getMasterKey();
            openssl_pkey_export($masterKey, $this->privateMasterKey);
        }
        return $this->privateMasterKey;
    }

    /**
     * Returns the master key resource identifier
     *
     * @return resource
     */
    private function getMasterKey()
    {
        if (!$this->masterKey) {
            $certificatePath = 'file://'.$this->masterKeyData['cert_file'];
            $passPhrase = $this->masterKeyData['passphrase'];

            $this->masterKey = openssl_pkey_get_private($certificatePath, $passPhrase);
            if (!$this->masterKey) {
                throw new EncryptionException('Could not open master encryption key');
            }
        }
        return $this->masterKey;
    }
}