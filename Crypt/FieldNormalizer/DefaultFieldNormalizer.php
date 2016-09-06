<?php

namespace EHEncryptionBundle\Crypt\FieldNormalizer;

class DefaultFieldNormalizer implements EncryptedFieldNormalizerInterface
{
    public function __construct()
    {

    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \EHEncryptionBundle\Crypt\FieldNormalizer\EncryptedFieldNormalizerInterface::normalize()
     */
    public function normalize($clearValue)
    {
        return $clearValue;
    }
}