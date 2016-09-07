<?php

namespace Module7\EncryptionBundle\Crypt\FieldMapping;

use Module7\EncryptionBundle\Exception\EncryptionException;

/**
 * Implementation of the EncryptedFieldMappingInterface for primitive values other than the text ones
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class PrimitiveFieldMapping extends AbstractEncryptedFieldMapping
{
    private static $allowedPrimitives = array(
        'boolean' => array('type' => 'string', 'length' => '50'),
        'smallint' => array('type' => 'string', 'length' => '50'),
        'integer' => array('type' => 'string', 'length' => '50'),
        'bigint' => array('type' => 'string', 'length' => '100'),
        'float' => array('type' => 'string', 'length' => '200'),
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