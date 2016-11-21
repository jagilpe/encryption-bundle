<?php

namespace Module7\EncryptionBundle\Metadata;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Metadata\Driver\DriverChain;
use Metadata\Driver\DriverInterface;
use Metadata\Driver\FileLocator;
use Module7\EncryptionBundle\Metadata\Driver\AnnotationDriver;
use Module7\EncryptionBundle\Metadata\Driver\YamlDriver;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

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
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var bool
     */
    private $metadataLoaded = false;

    /**
     * @var array
     */
    private $metadataDirs;

    /**
     * @var DriverInterface
     */
    private $driver;

    public function __construct(
        Reader $reader,
        Registry $doctrine,
        KernelInterface $kernel,
        array $settings
    )
    {
        $this->reader = $reader;
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
        $this->settings = $settings;
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

            /** @var ClassMetadata $doctrineClassMetadata */
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
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMetadataDriver()->loadMetadataForClass($reflectionClass);
        if (null === $classMetadata->encryptionMode) {
            $classMetadata->encryptionMode = $this->settings['default_mode'];
        }
        return $classMetadata;
    }

    /**
     * Returns the directories in which to look for the encryption metadata
     *
     * @return array
     */
    private function getMetadataDirs()
    {
        if (null === $this->metadataDirs) {
            $this->metadataDirs = array();
            $bundles = $this->kernel->getBundles();
            $fs = new Filesystem();

            /**
             * @var string $bundleName
             * @var BundleInterface $bundle
             */
            foreach ($bundles as $bundleName => $bundle) {
                $directory = $this->kernel->locateResource("@$bundleName/Resources");
                $directory .= '/config/m7_encryption';
                if ($fs->exists($directory)) {
                    $nameSpace = $bundle->getNamespace();
                    $this->metadataDirs[$nameSpace] = $directory;
                }
            }
        }

        return $this->metadataDirs;
    }

    /**
     * Returns the metadata driver
     *
     * @return DriverInterface
     */
    private function getMetadataDriver()
    {
        if (null === $this->driver) {
            $metadataDirs = $this->getMetadataDirs();

            $annotationDriver = new AnnotationDriver($this->reader);
            if (!empty($metadataDirs)) {
                $fileLocator = new FileLocator($metadataDirs);
                $ymlDriver = new YamlDriver($fileLocator);
                $this->driver = new DriverChain(array(
                    $ymlDriver,
                    $annotationDriver,
                ));

                $ymlDriver->setDriver($this->driver);
            }
            else {
                $this->driver = $annotationDriver;
            }
        }

        return $this->driver;
    }
}