<?php

namespace EHEncryptionBundle\Crypt\FieldMapping;

class TextFieldMapping extends AbstractEncryptedFieldMapping
{
    /**
     * {@inheritdoc}
     */
    public function getMappingAttributeOverride()
    {
        $fieldMapping = $this->getFieldMapping();
        $fieldMapping['type'] = 'text';

        return $fieldMapping;
    }
}