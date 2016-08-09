<?php

namespace EHEncryptionBundle\Crypt\FieldMapping;

use EHEncryptionBundle\Service\EncryptionService;

abstract class AbstractEncryptedFieldMapping implements EncryptedFieldMappingInterface
{
    /**
     * @var \EHEncryptionBundle\Service\EncryptionService
     */
    protected $encryptionService;

    /**
     * @var array
     */
    private $fieldMapping;

    public function __construct(EncryptionService $encryptionService, array $fieldMapping)
    {
        $this->encryptionService = $encryptionService;
        $this->fieldMapping = $fieldMapping;
    }

    protected function getFieldMapping()
    {
        $fieldMapping = $this->fieldMapping;
        $fieldMapping['_old_type'] = $fieldMapping['type'];
        return $fieldMapping;
    }
}