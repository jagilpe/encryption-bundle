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
    protected $fieldMapping;

    public function __construct(EncryptionService $encryptionService, array $fieldMapping)
    {
        $this->encryptionService = $encryptionService;
        $this->fieldMapping = $fieldMapping;
    }
}