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
        openssl_private_decrypt($encryptedValue, $decryptedValue, $privateKey);

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
        $randomPass = openssl_random_pseudo_bytes($this->settings['symmetric_key_length'], $secure);
        $method = $this->getDigestMethod();

        return hash($method, $randomPass, true);
    }

    private function getCipherMethod($type = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        return $this->settings['cipher_method'][$type];
    }

    private function getDigestMethod()
    {
        return $this->settings['digest_method'];
    }
}