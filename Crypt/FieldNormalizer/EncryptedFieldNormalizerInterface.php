<?php

namespace EHEncryptionBundle\Crypt\FieldNormalizer;

interface EncryptedFieldNormalizerInterface
{
    /**
     * Normalizes the non encrypted value to the new format required by the encryption
     *
     * @param string $clearValue
     *
     * @return mixed
     */
    public function normalize($clearValue);
}