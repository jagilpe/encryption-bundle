<?php

namespace Jagilpe\EncryptionBundle\Crypt\FieldNormalizer;

/**
 * Implementation of the EncryptedFieldNormalizerInterface for simple array fields
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class SerializableObjectFieldNormalizer implements EncryptedFieldNormalizerInterface
{
    /**
     *
     * {@inheritdoc}
     *
     * @see \Jagilpe\EncryptionBundle\Crypt\FieldNormalizer\EncryptedFieldNormalizerInterface::normalize()
     */
    public function normalize($clearValue)
    {
        $normalizedValue = null;

        if ($clearValue !== null) {
            if (is_string($clearValue)) {
                $normalizedValue = unserialize($clearValue);
            }
            else {
                $normalizedValue = $clearValue;
            }
        }

        return $normalizedValue;
    }
}