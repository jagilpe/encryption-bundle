<?php

namespace Module7\EncryptionBundle\Metadata\Driver;

use Metadata\Driver\DriverInterface;
use Doctrine\Common\Annotations\Reader;
use Module7\EncryptionBundle\Annotation\EncryptedEntity;
use Module7\EncryptionBundle\Annotation\EncryptedField;
use Module7\EncryptionBundle\Annotation\EncryptedFile;
use Module7\EncryptionBundle\Metadata\ClassMetadata;
use Module7\EncryptionBundle\Metadata\PropertyMetadata;

/**
 * Class AnnotationDriver
 * @package Module7\EncryptionBundle\Metadata\Driver
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class AnnotationDriver implements DriverInterface
{
    /**
     * @var Reader;
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return \Metadata\ClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $reflectionClass)
    {
        $classMetadata = null;

        $encryptionAnnotation = $this->reader->getClassAnnotation(
            $reflectionClass,
            'Module7\\EncryptionBundle\\Annotation\\EncryptedEntity'
        );

        /** @var EncryptedEntity $encryptionAnnotation */
        if ($encryptionAnnotation) {
            $classMetadata = new ClassMetadata($name = $reflectionClass->getName());
            $classMetadata->fileResources[] = $reflectionClass->getFileName();

            $classMetadata->encryptionEnabled = $encryptionAnnotation->enabled;
            $classMetadata->encryptionMode = $encryptionAnnotation->mode;

            $encryptedFileAnnotation = $this->reader->getClassAnnotation(
                $reflectionClass,
                'Module7\\EncryptionBundle\\Annotation\\EncryptedFile'
            );

            /** @var EncryptedFile $encryptedFileAnnotation */
            if ($encryptedFileAnnotation) {
                $classMetadata->encryptedFile = $encryptedFileAnnotation->enabled;
                $classMetadata->encryptedFileMode = $encryptedFileAnnotation->mode;
            }

            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $propertyAnnotation = $this->reader->getPropertyAnnotation(
                    $reflectionProperty,
                    'Module7\\EncryptionBundle\\Annotation\\EncryptedField'
                );

                /** @var EncryptedField $propertyAnnotation */
                if ($propertyAnnotation) {
                    $propertyMetadata = new PropertyMetadata($reflectionClass->getName(), $reflectionProperty->getName());
                    $propertyMetadata->encrypted = $propertyAnnotation->enabled;

                    $classMetadata->addPropertyMetadata($propertyMetadata);
                }
            }
        }

        return $classMetadata;
    }

    /**
     * Returns the entity classes
     *
     * @return array
     */
    private function getEntityClasses()
    {

    }
}