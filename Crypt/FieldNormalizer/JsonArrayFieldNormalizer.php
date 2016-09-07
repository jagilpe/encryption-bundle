<?php

namespace Module7\EncryptionBundle\Crypt\FieldNormalizer;

use Module7\EncryptionBundle\Exception\EncryptionException;

/**
 * Implementation of the EncryptedFieldNormalizerInterface for json array fields
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class JsonArrayFieldNormalizer implements EncryptedFieldNormalizerInterface
{
    /**
     *
     * {@inheritdoc}
     *
     * @see \Module7\EncryptionBundle\Crypt\FieldNormalizer\EncryptedFieldNormalizerInterface::normalize()
     */
    public function normalize($clearValue)
    {
        $normalizedValue = null;

        if ($clearValue !== null) {
            if (is_string($clearValue)) {
                $normalizedValue = (array) json_decode($clearValue);
            }
            elseif (is_array($clearValue)) {
                $normalizedValue = $clearValue;
            }
            else {
                throw new EncryptionException('Value type not supported');
            }
        }

        return $normalizedValue;
    }
}