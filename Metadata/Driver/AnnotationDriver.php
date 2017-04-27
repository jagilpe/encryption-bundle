<?php

namespace Jagilpe\EncryptionBundle\Metadata\Driver;

use Metadata\Driver\DriverInterface;
use Doctrine\Common\Annotations\Reader;
use Jagilpe\EncryptionBundle\Annotation\EncryptedEntity;
use Jagilpe\EncryptionBundle\Annotation\EncryptedField;
use Jagilpe\EncryptionBundle\Annotation\EncryptedFile;
use Jagilpe\EncryptionBundle\Metadata\ClassMetadata;
use Jagilpe\EncryptionBundle\Metadata\PropertyMetadata;

/**
 * Class AnnotationDriver
 * @package Jagilpe\EncryptionBundle\Metadata\Driver
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
        $className = $reflectionClass->getName();
        $classMetadata = new ClassMetadata($className);
        $classMetadata->fileResources[] = $reflectionClass->getFileName();

        $encryptionAnnotation = $this->reader->getClassAnnotation(
            $reflectionClass,
            'Jagilpe\\EncryptionBundle\\Annotation\\EncryptedEntity'
        );
        /** @var EncryptedEntity $encryptionAnnotation */
        if ($encryptionAnnotation) {
            $classMetadata->encryptionEnabled = $encryptionAnnotation->enabled;
            $classMetadata->encryptionMode = $encryptionAnnotation->mode;

            $encryptedFileAnnotation = $this->reader->getClassAnnotation(
                $reflectionClass,
                'Jagilpe\\EncryptionBundle\\Annotation\\EncryptedFile'
            );

            /** @var EncryptedFile $encryptedFileAnnotation */
            if ($encryptedFileAnnotation) {
                $classMetadata->encryptedFile = $encryptedFileAnnotation->enabled;
                $classMetadata->encryptedFileMode = $encryptedFileAnnotation->mode;
            }

            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $propertyAnnotation = $this->reader->getPropertyAnnotation(
                    $reflectionProperty,
                    'Jagilpe\\EncryptionBundle\\Annotation\\EncryptedField'
                );

                /** @var EncryptedField $propertyAnnotation */
                if ($propertyAnnotation) {
                    $propertyMetadata = new PropertyMetadata($reflectionClass->getName(), $reflectionProperty->getName());
                    $propertyMetadata->encrypted = $propertyAnnotation->enabled;

                    $classMetadata->addPropertyMetadata($propertyMetadata);
                }
            }
        }
        else {
            $classMetadata->encryptionEnabled = false;
        }

        return $classMetadata;
    }
}