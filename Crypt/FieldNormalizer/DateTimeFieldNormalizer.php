<?php

namespace EHEncryptionBundle\Crypt\FieldNormalizer;

use EHEncryptionBundle\Exception\EncryptionException;

class DateTimeFieldNormalizer implements EncryptedFieldNormalizerInterface
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
                $normalizedValue = new \DateTime($clearValue);
            }
            elseif ($clearValue instanceof \DateTime) {
                $normalizedValue = $clearValue;
            }
            else {
                throw new EncryptionException('Value type not supported');
            }
        }

        return $normalizedValue;
    }
}