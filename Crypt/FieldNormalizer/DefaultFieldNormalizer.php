<?php

namespace Module7\EncryptionBundle\Crypt\FieldNormalizer;

/**
 * Default implementation of the EncryptedFieldNormalizerInterface
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class DefaultFieldNormalizer implements EncryptedFieldNormalizerInterface
{
    public function __construct()
    {

    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Module7\EncryptionBundle\Crypt\FieldNormalizer\EncryptedFieldNormalizerInterface::normalize()
     */
    public function normalize($clearValue)
    {
        return $clearValue;
    }
}