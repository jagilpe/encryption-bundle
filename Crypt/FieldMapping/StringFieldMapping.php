<?php

namespace EHEncryptionBundle\Crypt\FieldMapping;

class StringFieldMapping extends AbstractEncryptedFieldMapping
{
    /**
     * {@inheritdoc}
     */
    public function getMappingAttributeOverride()
    {
        $fieldMapping = $this->fieldMapping;
        $fieldMapping['length'] += 16;

        return $fieldMapping;
    }
}