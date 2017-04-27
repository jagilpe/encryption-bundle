<?php

namespace Module7\EncryptionBundle\Crypt;

/**
 * Manages all the encryption modes and algorithms
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface CryptographyProviderInterface
{
    const PROPERTY_ENCRYPTION = 'property';
    const FILE_ENCRYPTION = 'file';
    const PRIVATE_KEY_ENCRYPTION = 'private_key';

    /**
     * Encrypts a value using symmetric encryption
     *
     * @param string $value
     * @param KeyDataInterface $keyData
     *
     * @return string
     */
    public function encrypt($value, KeyDataInterface $keyData);

    /**
     * Decrypts a value using symmetric encryption
     *
     * @param string $value
     * @param KeyDataInterface $keyData
     *
     * @return string
     */
    public function decrypt($value, KeyDataInterface $keyData);

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
     * @param mixed $privateKey
     *
     * @return string
     */
    public function encryptWithPrivateKey($value, $privateKey);

    /**
     * Decrypts with a public key using asymmetric encryption
     *
     * @param string $value
     * @param mixed $privateKey
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

    /**
     * Returns the digest of the password used to encrypt the private key of the user
     *
     * @param string $password
     * @param string $salt
     * @param integer $iterations
     *
     * @return string
     */
    public function getPasswordDigest($password, $salt, $iterations);
}