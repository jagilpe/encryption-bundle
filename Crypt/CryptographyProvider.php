<?php

namespace EHEncryptionBundle\Crypt;

/**
 * Manages all the encryption modes and algorithms
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class CryptographyProvider implements CryptographyProviderInterface
{
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($value, KeyData $keyData)
    {
        $method = $this->getCipherMethod();
        return openssl_encrypt($value, $method, $keyData->getKey(), 0, $keyData->getIv());
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($value, KeyData $keyData)
    {
        $method = $this->getCipherMethod();
        return openssl_decrypt($value, $method, $keyData->getKey(), 0, $keyData->getIv());
    }

    /**
     * {@inheritdoc}
     */
    public function encryptWithPublicKey($value, $publicKey)
    {
        $encryptedValue = null;
        openssl_public_encrypt($value, $encryptedValue, $publicKey);

        return $encryptedValue;
    }

    /**
     * {@inheritdoc}
     */
    public function decryptWithPublicKey($encryptedValue, $publicKey)
    {
        $decryptedValue = null;
        openssl_public_decrypt($encryptedValue, $decryptedValue, $publicKey);

        return $decryptedValue;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptWithPrivateKey($value, $privateKey)
    {
        $encryptedValue = null;
        openssl_private_encrypt($value, $encryptedValue, $privateKey);

        return $encryptedValue;
    }

    /**
     * {@inheritdoc}
     */
    public function decryptWithPrivateKey($encryptedValue, $privateKey)
    {
        $decryptedValue = null;
        $result = openssl_private_decrypt($encryptedValue, $decryptedValue, $privateKey);

        return $decryptedValue;
    }

    /**
     * {@inheritdoc}
     */
    public function generateIV($type = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        $method = $this->getCipherMethod($type);
        $ivLength = openssl_cipher_iv_length($method);
        $secure = true;
        $iv = openssl_random_pseudo_bytes($ivLength, $secure);

        if ($secure) {
            return $iv;
        }
        else {
            throw new \Exception();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateSecureKey()
    {
        $randomPass = openssl_random_pseudo_bytes($this->getSymmetricKeyLength(), $secure);
        return $randomPass;
    }

    private function getCipherMethod($type = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        return $this->settings['cipher_method'][$type];
    }

    /**
     * Returns the key length for the desired encryption method
     *
     * @param string $type
     *
     * @return integer
     */
    private function getSymmetricKeyLength($type = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        $cipherMethod = $this->getCipherMethod($type);

        switch ($cipherMethod) {
            case 'AES-128-CBC':
                $length = 16;
                break;
            case 'AES-192-CBC':
                $length = 24;
                break;
            case 'AES-256-CBC':
                $length = 32;
                break;
            default:
                $length = 16;
                break;
        }

        return $length;
    }

    private function getDigestMethod()
    {
        return $this->settings['digest_method'];
    }
}