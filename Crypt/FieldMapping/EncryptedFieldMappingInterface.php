<?php

namespace Module7\EncryptionBundle\Crypt\FieldMapping;

/**
 * Defines an interface to get the new mapping metadata for the encrypted fields
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface EncryptedFieldMappingInterface
{
    /**
     * Returns the overrides in the field metadata required to hold the encrypted value of the field
     *
     * @return array
     */
    public function getMappingAttributeOverride();
}