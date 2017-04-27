<?php

namespace Jagilpe\EncryptionBundle\Crypt\FieldEncrypter;

use Jagilpe\EncryptionBundle\Crypt\CryptographyProviderInterface;
use Jagilpe\EncryptionBundle\Crypt\KeyDataInterface;

/**
 * Default implementation of the FieldEncrypterInterface
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class DefaultFieldEncrypter implements EncryptedFieldEncrypterInterface
{
    /**
     * @var \Jagilpe\EncryptionBundle\Crypt\CryptographyProviderInterface
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