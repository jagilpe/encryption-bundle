<?php

namespace EHEncryptionBundle\Crypt;

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