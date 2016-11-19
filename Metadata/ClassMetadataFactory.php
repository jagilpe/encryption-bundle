<?php

namespace Module7\EncryptionBundle\Metadata;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Metadata\Driver\DriverChain;
use Metadata\Driver\DriverInterface;
use Module7\EncryptionBundle\Metadata\Driver\AnnotationDriver;

/**
 * Class ClassMetadataFactory
 * @package Module7\EncryptionBundle\Metadata
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class ClassMetadataFactory
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var bool
     */
    private $metadataLoaded = false;

    /**
     * @var DriverInterface
     */
    private $driver;

    public function __construct(Reader $reader, Registry $doctrine)
    {
        $this->reader = $reader;
        $this->doctrine = $doctrine;
        // Initialize the metadata driver
        $this->driver = new DriverChain(array(
            new AnnotationDriver($this->reader),
        ));
    }

    /**
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     *
     * @return array The ClassMetadata instances of all mapped classes.
     */
    public function getAllMetadata()
    {
        if (!$this->metadataLoaded) {
            // We have to load all the metadata
            $doctrineMetadata = $this->doctrine->getManager()->getMetadataFactory();

            /** @var ClassMetadataInfo $doctrineClassMetadata */
            foreach ($doctrineMetadata->getAllMetadata() as $doctrineClassMetadata) {
                $reflectionClass = $doctrineClassMetadata->getReflectionClass();
                $this->metadata[$reflectionClass->getName()] = $this->getClassMetadata($reflectionClass);
            }

            $this->metadataLoaded = true;
        }

        $allMetadata = array_filter($this->metadata, function($element) { return null !== $element; });

        return $allMetadata;
    }

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     *
     * @return ClassMetadata
     */
    public function getMetadataFor($className)
    {
        if (!isset($this->metadata[$className])) {
            $reflectionClass = ClassUtils::newReflectionClass($className);
            $this->metadata[$className] = $this->getClassMetadata($reflectionClass);
        }

        return $this->metadata[$className];
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     *
     * @param string $className
     *
     * @return boolean TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMetadataFor($className)
    {
        $metadata = $this->getAllMetadata();
        return isset($metadata[$className]);
    }

    /**
     * Returns the metadata for a given class
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return ClassMetadata
     */
    private function getClassMetadata(\ReflectionClass $reflectionClass)
    {
        return $this->driver->loadMetadataForClass($reflectionClass);
    }
}