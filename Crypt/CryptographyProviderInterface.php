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

    public function encrypt($value, $key, $iv);

    public function decrypt($value, $key, $iv);

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