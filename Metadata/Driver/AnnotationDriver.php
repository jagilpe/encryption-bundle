<?php

namespace EHEncryptionBundle\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\DriverInterface;
use EHEncryptionBundle\Metadata\ClassMetadata;
use EHEncryptionBundle\Annotation\EncryptedEntity;
use EHEncryptionBundle\Annotation\EncryptedField;
use EHEncryptionBundle\Annotation\EncryptedFile;

class AnnotationDriver implements DriverInterface
{
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $reflection)
    {
        $classMetadata = new ClassMetadata($reflection);

        foreach ($this->reader->getClassAnnotations($reflection) as $annotation) {
            if ($annotation instanceof EncryptedEntity) {
                $classMetadata->enabled = $annotation->enabled;
                $classMetadata->mode = $annotation->mode;
            }
            elseif ($annotation instanceof EncryptedFile) {
                $classMetadata->encryptedFile = $annotation->enabled;
            }
        }

        return $classMetadata;
    }
}
