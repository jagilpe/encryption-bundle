<?php

namespace Module7\EncryptionBundle\Crypt\FieldMapping;

/**
 * Implementation of the FieldEncrypterInterface for string fields
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
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