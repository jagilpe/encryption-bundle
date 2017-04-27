<?php

namespace Module7\EncryptionBundle\Crypt\FieldMapping;

/**
 * Implementation of the FieldMappingInterface for DateTime fields
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class DateTimeFieldMapping extends AbstractEncryptedFieldMapping
{
    /**
     * {@inheritdoc}
     */
    public function getMappingAttributeOverride()
    {
        // We will save the date/datetime as a serialized object
        // The max length of a serialized \DateTime is 142 (if timezone is 'America/Argentina/Buenos_Aires')
        // We give more space to be able to store the encrypted value
        $fieldMapping = $this->getFieldMapping();
        $fieldMapping['type'] = 'string';
        $fieldMapping['length'] = 200;

        return $fieldMapping;
    }
}