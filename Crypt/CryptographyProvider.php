<?php

namespace EHEncryptionBundle\Crypt;

use EHEncryptionBundle\Exception\EncryptionException;

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
    public function encrypt($value, KeyData $keyData, $encType = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        $method = $this->getCipherMethod($encType);
        $encryptionOptions = $this->getEncryptionOptions($encType);
        return openssl_encrypt($value, $method, $keyData->getKey(), $encryptionOptions, base64_decode($keyData->getIv()));
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($value, KeyData $keyData, $encType = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        $method = $this->getCipherMethod($encType);
        $encryptionOptions = $this->getEncryptionOptions($encType);
        return openssl_decrypt($value, $method, $keyData->getKey(), $encryptionOptions, base64_decode($keyData->getIv()));
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
    public function generateIV($encType = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        $method = $this->getCipherMethod($encType);
        $ivLength = openssl_cipher_iv_length($method);
        $secure = true;
        $iv = openssl_random_pseudo_bytes($ivLength, $secure);

        if ($secure) {
            return base64_encode($iv);
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

    /**
     * {@inheritdoc}
     */
    public function getPasswordDigest($password, $salt, $iterations = 500)
    {
        $saltedPassword = $password.'{'.$salt.'}';

        $digest = hash('sha256', $saltedPassword, true);
        for ($i = 1; $i < $iterations; $i++) {
            $digest = hash('sha256', $digest.$saltedPassword, true);
        }

        return $digest;
    }

    /**
     * Returns the cipher method corresponding to a determined element type
     *
     * @param string $type
     *
     * @return string
     */
    private function getCipherMethod($encType = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        $method = $this->settings['cipher_method'][$encType];
        $supportedMethods = openssl_get_cipher_methods();

        if (!in_array($method, $supportedMethods)) {
            throw new EncryptionException('Method '.$method.' not supported by openssl installation.');
        }

        return $method;
    }

    /**
     * Returns the key length for the desired encryption method
     *
     * @param string $type
     *
     * @return integer
     */
    private function getSymmetricKeyLength($encType = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        $cipherMethod = $this->getCipherMethod($encType);

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

    /**
     * Returns the configured digest method
     *
     * @return string
     */
    private function getDigestMethod()
    {
        return $this->settings['digest_method'];
    }

    /**
     * Returns the options for the encryption depending on the encryption type
     *
     * @param string $type
     *
     * @return integer
     */
    private function getEncryptionOptions($type = CryptographyProviderInterface::PROPERTY_ENCRYPTION)
    {
        switch ($type) {
            case CryptographyProviderInterface::PROPERTY_ENCRYPTION:
            case CryptographyProviderInterface::PRIVATE_KEY_ENCRYPTION:
                return 0;
            case CryptographyProviderInterface::FILE_ENCRYPTION:
                return OPENSSL_RAW_DATA;
            default:
                throw new EncryptionException('Encryption type not supported: '.$type);
        }
    }
}