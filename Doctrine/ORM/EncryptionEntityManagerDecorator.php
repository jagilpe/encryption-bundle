<?php

namespace EHEncryptionBundle\Doctrine\ORM;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use EHEncryptionBundle\Service\EncryptionService;

class EncryptionEntityManagerDecorator extends EntityManagerDecorator
{
    /**
     * @var EncryptionService
     */
    private $encryptionService;

    public function __construct($wrapped, EncryptionService $encryptionService)
    {
        parent::__construct($wrapped);

        $this->encryptionService = $encryptionService;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        $originalRepository = parent::getRepository($className);

        // Check if the class is an encryptable entity
        $classMetadata = $this->getClassMetadata($className);
        if ($this->encryptionService->hasEncryptionEnabled($classMetadata->getReflectionClass(), $classMetadata)) {
            $repository = new EncryptionEntityRepositoryDecorator($originalRepository, $classMetadata, $this->encryptionService);
        }
        else {
            $repository = $originalRepository;
        }

        return $repository;
    }
}