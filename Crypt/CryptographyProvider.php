<?php

namespace EHEncryptionBundle\Crypt;

/**
 * Manages all the encryption modes and algorithms
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class CryptographyProvider implements CryptographyProviderInterface
{
    private $keyManager;

    public function __construct(KeyManager $keyManager)
    {
        $this->keyManager = $keyManager;
    }
}