<?php

namespace Module7\EncryptionBundle\Crypt\FieldNormalizer;

/**
 * Defines an interface to get convert the original values of a field to the one required by the encryption module
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
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