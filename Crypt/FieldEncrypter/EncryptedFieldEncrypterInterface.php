<?php

namespace EHEncryptionBundle\Crypt\FieldEncrypter;

use EHEncryptionBundle\Crypt\KeyDataInterface;

interface EncryptedFieldEncrypterInterface
{
    /**
     * Encrypts the value of the field
     *
     * @param mixed $clearValue
     * @param \EHEncryptionBundle\Crypt\KeyDataInterface $keyData
     *
     * @return string
     */
    public function encrypt($clearValue, KeyDataInterface $keyData);

    /**
     * Decrypts the value of the field
     *
     * @param string $encryptedValue
     * @param \EHEncryptionBundle\Crypt\KeyDataInterface $keyData
     *
     * @return mixed
     */
    public function decrypt($encryptedValue, KeyDataInterface $keyData);
}