<?php

namespace Jagilpe\EncryptionBundle\Crypt;

/**
 * Interface for the key data required for symetric encryption
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 */
interface KeyDataInterface
{
    /**
     * Returns the encryption key
     */
    public function getKey();

    /**
     * Returns the initilization vector
     */
    public function getIv();
}