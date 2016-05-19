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
    public function encrypt($value, $key, $iv)
    {
        $method = $this->getCipherMethod();
        return openssl_encrypt($value, $method, $key, 0, $iv);
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($value, $key, $iv)
    {
        $method = $this->getCipherMethod();
        return openssl_decrypt($value, $method, $key, 0, $iv);
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
        $randomPass = openssl_random_pseudo_bytes($this->settings['key_length'], $secure);
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