<?php

namespace Jagilpe\EncryptionBundle\Metadata\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Jagilpe\EncryptionBundle\Exception\EncryptionException;
use Jagilpe\EncryptionBundle\Metadata\ClassMetadata;
use Jagilpe\EncryptionBundle\Metadata\PropertyMetadata;
use Jagilpe\EncryptionBundle\Service\EncryptionService;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlDriver
 * @package Jagilpe\EncryptionBundle\Metadata\Driver
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class YamlDriver extends AbstractFileDriver
{
    /**
     * @var DriverInterface
     */
    private $driver;

    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

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
        $classMetadata = $this->generateClassMetadata($reflectionClass);

        $config = Yaml::parse(file_get_contents($file));
        if ( ! isset($config[$className])) {
            throw new EncryptionException(sprintf('Expected metadata for class %s to be defined in %s.', $className, $file));
        }

        $classConfig = $config[$className];
        $parentClassMetadata = $classMetadata->parentClassMetadata;
        $encryptionEnabled = ($parentClassMetadata && $parentClassMetadata->encryptionEnabled)
            || (isset($classConfig['encryptionEnabled']) && $classConfig['encryptionEnabled']);

        if ($encryptionEnabled) {
            $classMetadata->encryptionEnabled = true;
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
            elseif ($parentClassMetadata) {
                $classMetadata->encryptionMode = $parentClassMetadata->encryptionMode;
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
            elseif ($parentClassMetadata) {
                $classMetadata->encryptedFile = $parentClassMetadata->encryptedFile;
                $classMetadata->encryptedFileMode = $parentClassMetadata->encryptedFileMode;
            }

            // Add the encrypted fields of the parent
            if ($parentClassMetadata) {
                $parentPropertiesMetadata = $parentClassMetadata->propertyMetadata;
                foreach ($parentPropertiesMetadata as $parentPropertyMetadata) {
                    $classMetadata->addPropertyMetadata($parentPropertyMetadata);
                }
            }

            // Add the self encrypted fields
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

    private function generateClassMetadata(\ReflectionClass $reflectionClass)
    {
        $className = $reflectionClass->getName();
        $classMetadata = new ClassMetadata($className);
        $classMetadata->fileResources[] = $reflectionClass->getFileName();

        $parentClass = $reflectionClass->getParentClass();
        if ($this->driver && $parentClass) {
            $parentMetadata = $this->driver->loadMetadataForClass($parentClass);
            $classMetadata->parentClassMetadata = $parentMetadata;
        }

        return $classMetadata;
    }
}