<?php

namespace Jagilpe\EncryptionBundle\Crypt\FieldEncrypter;

use Jagilpe\EncryptionBundle\Crypt\KeyDataInterface;

/**
 * Defines an interface for the encryption of the content of a determined field
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface EncryptedFieldEncrypterInterface
{
    /**
     * Encrypts the value of the field
     *
     * @param mixed $clearValue
     * @param \Jagilpe\EncryptionBundle\Crypt\KeyDataInterface $keyData
     *
     * @return string
     */
    public function encrypt($clearValue, KeyDataInterface $keyData);

    /**
     * Decrypts the value of the field
     *
     * @param string $encryptedValue
     * @param \Jagilpe\EncryptionBundle\Crypt\KeyDataInterface $keyData
     *
     * @return mixed
     */
    public function decrypt($encryptedValue, KeyDataInterface $keyData);
}