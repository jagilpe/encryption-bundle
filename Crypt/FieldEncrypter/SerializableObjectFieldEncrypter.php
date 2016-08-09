<?php

namespace EHEncryptionBundle\Crypt\FieldEncrypter;

use EHEncryptionBundle\Crypt\KeyDataInterface;

class SerializableObjectFieldEncrypter extends DefaultFieldEncrypter
{
    /**
     * {@inheritdoc}
     */
    public function encrypt($clearValue, KeyDataInterface $keyData)
    {
        $serializedValue = $clearValue !== null ? serialize($clearValue) : null;
        return parent::encrypt($serializedValue, $keyData);
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($encryptedValue, KeyDataInterface $keyData)
    {
        $serializedValue = parent::decrypt($encryptedValue, $keyData);
        return $serializedValue !== null ? unserialize($serializedValue) : null;
    }
}