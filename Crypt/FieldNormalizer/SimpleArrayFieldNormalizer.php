<?php

namespace EHEncryptionBundle\Crypt\FieldNormalizer;

use EHEncryptionBundle\Exception\EncryptionException;

class SimpleArrayFieldNormalizer implements EncryptedFieldNormalizerInterface
{
    /**
     *
     * {@inheritdoc}
     *
     * @see \EHEncryptionBundle\Crypt\FieldNormalizer\EncryptedFieldNormalizerInterface::normalize()
     */
    public function normalize($clearValue)
    {
        $normalizedValue = null;

        if ($clearValue !== null) {
            if (is_string($clearValue)) {
                $normalizedValue = explode(',', $clearValue);
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