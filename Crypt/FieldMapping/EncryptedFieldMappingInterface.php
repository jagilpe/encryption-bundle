<?php

namespace EHEncryptionBundle\Crypt\FieldMapping;

interface EncryptedFieldMappingInterface
{
    /**
     * Returns the overrides in the field metadata required to hold the encrypted value of the field
     *
     * @return array
     */
    public function getMappingAttributeOverride();
}