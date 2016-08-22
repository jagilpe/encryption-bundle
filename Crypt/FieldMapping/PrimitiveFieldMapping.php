<?php

namespace EHEncryptionBundle\Crypt\FieldMapping;

use EHEncryptionBundle\Exception\EncryptionException;

class PrimitiveFieldMapping extends AbstractEncryptedFieldMapping
{
    private static $allowedPrimitives = array(
        'boolean' => array('type' => 'string', 'length' => '50'),
    );

    /**
     * {@inheritdoc}
     */
    public function getMappingAttributeOverride()
    {
        $fieldMapping = $this->getFieldMapping();

        $fieldType = $fieldMapping['_old_type'];
        if (!isset(self::$allowedPrimitives[$fieldType])) {
            throw new EncryptionException('Field type not supported: '.$fieldType);
        }

        $newMappingValues =  self::$allowedPrimitives[$fieldType];

        $fieldMapping['type'] = $newMappingValues['type'];
        $fieldMapping['length'] = $newMappingValues['length'];

        return $fieldMapping;
    }
}