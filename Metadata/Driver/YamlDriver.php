<?php

namespace Module7\EncryptionBundle\Metadata\Driver;

use Metadata\Driver\AbstractFileDriver;
use Module7\EncryptionBundle\Exception\EncryptionException;
use Module7\EncryptionBundle\Metadata\ClassMetadata;
use Module7\EncryptionBundle\Metadata\PropertyMetadata;
use Module7\EncryptionBundle\Service\EncryptionService;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlDriver
 * @package Module7\EncryptionBundle\Metadata\Driver
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class YamlDriver extends AbstractFileDriver
{
    /**
     * Loads the class metadata for the given class from the given file
     *
     * @param \ReflectionClass $reflectionClass
     * @param string $file
     *
     * @return ClassMetadata
     *
     * @throws EncryptionException
     */
    protected function loadMetadataFromFile(\ReflectionClass $reflectionClass, $file)
    {
        $className = $reflectionClass->getName();
        $classMetadata = new ClassMetadata($className);
        $classMetadata->fileResources[] = $reflectionClass->getFileName();


        $config = Yaml::parse(file_get_contents($file));
        if ( ! isset($config[$className])) {
            throw new EncryptionException(sprintf('Expected metadata for class %s to be defined in %s.', $className, $file));
        }

        $classConfig = $config[$className];

        if ($classConfig['encryptionEnabled']) {
            $classMetadata->encryptionEnabled = $classConfig['encryptionEnabled'];
            $encryptedFields = isset($classConfig['encryptedFields']) && is_array($classConfig['encryptedFields'])
                ? $classConfig['encryptedFields']
                : array();

            if (isset($classConfig['encryptionMode'])) {
                $encryptionMode = $classConfig['encryptionMode'];
                if (!in_array($encryptionMode, EncryptionService::getSupportedEncryptionModes())) {
                    throw new EncryptionException(sprintf('Encryption mode %s not supported.', $encryptionMode));
                }
                $classMetadata->encryptionMode = $encryptionMode;
            }

            if (isset($classConfig['encryptedFile'])) {
                $classMetadata->encryptedFile = $classConfig['encryptedFile'];
                if (isset($classConfig['encryptedFileMode'])) {
                    $encryptedFileMode = $classConfig['encryptedFileMode'];
                    if (!in_array($encryptedFileMode, EncryptionService::getSupportedEncryptionModes())) {
                        throw new EncryptionException(sprintf('Encryption file mode %s not supported.', $encryptedFileMode));
                    }
                    $classMetadata->encryptedFileMode = $encryptedFileMode;
                }
            }

            foreach ($encryptedFields as $fieldName => $encryptedField) {
                $reflectionProperty = $reflectionClass->hasProperty($fieldName)
                    ? $reflectionClass->getProperty($fieldName)
                    : null;

                if ($reflectionProperty) {
                    $propertyMetadata = new PropertyMetadata($reflectionClass->getName(), $reflectionProperty->getName());

                    if (null === $encryptedField) {
                        $propertyMetadata->encrypted = true;
                    }
                    else {
                        $propertyMetadata->encrypted = isset($encryptedField['encrypted']) ? $encryptedField['encrypted'] : false;
                    }
                    $classMetadata->addPropertyMetadata($propertyMetadata);
                }
            }
        }

        return $classMetadata;

    }

    /**
     * @return string
     */
    protected function getExtension()
    {
        return 'yml';
    }
}