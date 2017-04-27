<?php

namespace Jagilpe\EncryptionBundle\Crypt\FieldMapping;

/**
 * Implementation of the FieldEncrypterInterface for text fields
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
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