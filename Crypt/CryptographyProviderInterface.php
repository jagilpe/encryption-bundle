<?php

namespace EHEncryptionBundle\Crypt;

/**
 * Manages all the encryption modes and algorithms
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface CryptographyProviderInterface
{
    const PROPERTY_ENCRYPTION = 'property';
    const FILE_ENCRYPTION = 'file';

    /**
     * Encrypts a value using symmetric encryption
     *
     * @param string $value
     * @param KeyData $keyData
     *
     * @return string
     */
    public function encrypt($value, KeyData $keyData);

    /**
     * Decrypts a value using symmetric encryption
     *
     * @param string $value
     * @param KeyData $keyData
     *
     * @return string
     */
    public function decrypt($value, KeyData $keyData);

    /**
     * Encrypts with a public key using asymmetric encryption
     *
     * @param string $value
     * @param mixed $publicKey
     *
     * @return string
     */
    public function encryptWithPublicKey($value, $publicKey);

    /**
     * Decrypts with a public key using asymmetric encryption
     *
     * @param string $value
     * @param mixed $publicKey
     *
     * @return string
     */
    public function decryptWithPublicKey($value, $publicKey);

    /**
     * Encrypts with a public key using asymmetric encryption
     *
     * @param string $value
     * @param mixed $publicKey
     *
     * @return string
     */
    public function encryptWithPrivateKey($value, $privateKey);

    /**
     * Decrypts with a public key using asymmetric encryption
     *
     * @param string $value
     * @param mixed $publicKey
     *
     * @return string
     */
    public function decryptWithPrivateKey($value, $privateKey);

    /**
     * Generates an Initialization Vector
     *
     * @param string $type
     *
     * @return string
     */
    public function generateIV($type = self::PROPERTY_ENCRYPTION);

    /**
     * Generates an secure key
     *
     * @return string
     */
    public function generateSecureKey();
}