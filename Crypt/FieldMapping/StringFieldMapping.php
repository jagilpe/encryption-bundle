<?php

namespace EHEncryptionBundle\Crypt\FieldMapping;

class StringFieldMapping extends AbstractEncryptedFieldMapping
{
    /**
     * {@inheritdoc}
     */
    public function getMappingAttributeOverride()
    {
        $fieldMapping = $this->getFieldMapping();
        $fieldMapping['length'] += 16;

        return $fieldMapping;
    }
}