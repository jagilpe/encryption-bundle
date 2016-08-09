<?php

namespace EHEncryptionBundle\Crypt\FieldEncrypter;

use EHEncryptionBundle\Crypt\CryptographyProviderInterface;
use EHEncryptionBundle\Crypt\KeyDataInterface;

class DefaultFieldEncrypter implements EncryptedFieldEncrypterInterface
{
    /**
     * @var \EHEncryptionBundle\Crypt\CryptographyProviderInterface
     */
    private $cryptographyProvider;

    public function __construct(CryptographyProviderInterface $cryptographyProvider)
    {
        $this->cryptographyProvider = $cryptographyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($clearValue, KeyDataInterface $keyData)
    {
        return $clearValue !== null ? $this->cryptographyProvider->encrypt($clearValue, $keyData) : $clearValue;
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($encryptedValue, KeyDataInterface $keyData)
    {
        return $encryptedValue !== null ? $this->cryptographyProvider->decrypt($encryptedValue, $keyData) : $encryptedValue;
    }
}