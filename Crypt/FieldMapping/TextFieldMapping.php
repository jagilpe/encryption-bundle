<?php

namespace EHEncryptionBundle\Crypt\FieldMapping;

class TextFieldMapping extends AbstractEncryptedFieldMapping
{
    /**
     * {@inheritdoc}
     */
    public function getMappingAttributeOverride()
    {
        return $this->getFieldMapping();
    }
}