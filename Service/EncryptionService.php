<?php

namespace EHEncryptionBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Annotations\Reader;
use Metadata\MetadataFactoryInterface;
use EHEncryptionBundle\Annotation\EncryptedEntity;
use EHEncryptionBundle\Crypt\CryptographyProviderInterface;
use EHEncryptionBundle\Crypt\KeyManagerInterface;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

/**
 * Encapsulates the core encryption logic
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class EncryptionService
{
    const ENCRYPT = 'encrypt';
    const DECRYPT = 'decrypt';

    /**
     * Supported entity encryption modes
     */
    const MODE_PER_USER_SHAREABLE = 'PER_USER_SHAREABLE';

    /**
     * @var Metadata\MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var Doctrine\Common\Annotations\Reader
     */
    private $reader;

    /**
     * @var EHEncryptionBundle\Crypt\CryptographyProviderInterface
     */
    private $cryptographyProvider;

    /**
     * @var EHEncryptionBundle\Crypt\KeyManagerInterface
     */
    private $keyManager;

    /**
     * @var array
     */
    private $settings;

    public function __construct(
                    MetadataFactoryInterface $metataFactory,
                    Reader $reader,
                    CryptographyProviderInterface $cryptographyProvider,
                    KeyManagerInterface $keyManager,
                    $settings)
    {
        $this->metadataFactory = $metataFactory;
        $this->reader = $reader;
        $this->cryptographyProvider = $cryptographyProvider;
        $this->keyManager = $keyManager;
        $this->settings = $settings;
    }

    /**
     * Adds the metadata required to encrypt the doctrine entity
     *
     * @param ClassMetadataInfo $metadata
     * @return \Doctrine\ORM\Mapping\ClassMetadataInfo
     */
    public function addEncryptionMetadata(ClassMetadataInfo $metadata)
    {
        $reflection = $metadata->getReflectionClass();

        if ($hasEncryptionEnabled = $this->hasEncryptionEnabled($reflection)) {
            if ($this->keyPerEntityRequired($hasEncryptionEnabled)) {
                // Add the field required to hold the key used to encrypt this entity
                $keyField = array(
                    'fieldName' => 'key',
                    'columnName' => '_key',
                    'type' => 'array',
                    'nullable' => true,
                );
                $metadata->mapField($keyField);

                // Add the field required to hold the initialization vector used to encrypt this entity
                $ivField = array(
                    'fieldName' => 'iv',
                    'columnName' => '_iv',
                    'type' => 'text',
                    'nullable' => true,
                );
                $metadata->mapField($ivField);
            }

            $encryptedField = array(
                'fieldName' => 'encrypted',
                'columnName' => '_encrypted',
                'type' => 'boolean',
            );
            $metadata->mapField($encryptedField);
        }

        return $metadata;
    }

    /**
     * Process all encryption related actions on an Entity pre persist event
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    public function processEntityPrePersist($entity)
    {
        // Check if this is the user entity
        $reflectionClass = new \ReflectionClass($entity);
        if ($this->isPKEncryptionEnabledUser($reflectionClass)) {
            // We have to generate the public and private encryption keys
            $this->keyManager->generateUserPKIKeys($entity);
        }

        // Process the encryption of the entity
        $this->processEntity($entity, self::ENCRYPT);
    }

    /**
     * Process all encryption related actions on an Entity pre persist event
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    public function processEntityPreUpdate($entity)
    {
        // Process the encryption of the entity
        $this->processEntity($entity, self::ENCRYPT);
    }

    /**
     * Process all encryption related actions on an Entity post load event
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    public function processEntityPostLoad($entity)
    {
        // Process the encryption of the entity
        $this->processEntity($entity, self::DECRYPT);
    }

    /**
     * Checks if the entity has encryption enabled and is actually encrypted
     *
     * @param mixed $entity
     *
     * @return boolean
     */
    public function isEntityEncrypted($entity)
    {
        $reflection = new \ReflectionClass($entity);

        return $this->hasEncryptionEnabled($reflection)
            && $reflection->hasMethod('isEncrypted')
            && $entity->isEncrypted();
    }

    /**
     * Checks if the entity has file encryption enabled
     *
     * @param mixed $entity
     *
     * @return boolean
     */
    public function isEncryptableFile($entity)
    {
        $isEncryptableFile = false;

        $reflection = new \ReflectionClass($entity);
        if ($this->hasEncryptionEnabled($reflection)) {
            $encryptableFile = $this->reader->getClassAnnotation(
                $reflection,
                'EHEncryptionBundle\\Annotation\\EncryptedFile'
            );

            $isEncryptableFile = $encryptableFile ? $encryptableFile->enabled : false;
        }

        return $isEncryptableFile;
    }

    /**
     * Processes an entity is it has encryption enabled and it's not already processed
     *
     * @param mixed $entity
     * @param string $operation
     *
     * @return mixed
     */
    private function processEntity($entity, $operation)
    {
        if ($this->settings[$operation.'_on_backend']) {
            $reflection = new \ReflectionClass($entity);

            if ($this->hasEncryptionEnabled($reflection) && $this->toProcess($entity, $operation)) {
                // Get the encryption key data
                $keyData = $this->keyManager->getEntityEncryptionKeyData($entity);

                // get the encrypted fields
                $encryptionEnabledFields = $this->getEncryptionEnabledFields($reflection);

                // Encrypt the fields
                foreach ($encryptionEnabledFields as $field) {
                    $value = $this->getFieldValue($entity, $field);
                    $processedValue = $this->cryptographyProvider->{$operation}($value, $keyData);
                    $this->setFieldValue($entity, $field, $processedValue);
                }

                // Set the encryption flag
                $entity->setEncrypted($operation === self::ENCRYPT);
            }
        }

        return $entity;
    }

    /**
     * Checks if the entity has to be processed
     *
     * @param mixed $entity
     * @param unknown $operation
     *
     * @return boolean
     */
    private function toProcess($entity, $operation)
    {
        switch ($operation) {
            case self::ENCRYPT:
                return !$entity->isEncrypted();
            case self::DECRYPT:
                return $entity->isEncrypted();
            default:
                return false;
        }
    }

    /**
     * Checks if the class has been enabled for encryption
     *
     * @param \ReflectionClass $reflection
     * @return EHEncryptionBundle\Annotation\EncryptedEntity/null
     */
    private function hasEncryptionEnabled(\ReflectionClass $reflection)
    {
        $classMetadata = $this->metadataFactory->getMetadataForClass($reflection->getName());

        $encryptedEnabled = $this->reader->getClassAnnotation(
            $reflection,
            'EHEncryptionBundle\\Annotation\\EncryptedEntity'
        );

        return $encryptedEnabled;
    }

    /**
     * Checks if the class represents a user with public key encryption enabled
     *
     * @param \ReflectionClass $reflection
     * @return EHEncryptionBundle\Annotation\PKEncryptionEnabledUser / null
     */
    private function isPKEncryptionEnabledUser(\ReflectionClass $reflection)
    {
        return $this->userBasedEncryption()
            && ($reflection->implementsInterface(PKEncryptionEnabledUserInterface::class));
    }

    /**
     * Checks if the encryption mode is user based
     *
     * @return boolean
     */
    private function userBasedEncryption()
    {
        return $this->settings['mode'] === self::MODE_PER_USER_SHAREABLE;
    }

    /**
     * Checks if the entity needs a field to store a key for each instance
     *
     * @param string $mode
     * @return boolean
     */
    private function keyPerEntityRequired(EncryptedEntity $hasEncryptionEnabled)
    {
        return $this->userBasedEncryption();
    }

    /**
     * Checks the fields of the entity and returns a list of those with encryption enabled
     *
     * @param \ReflectionClass $reflection
     *
     * @return array
     */
    private function getEncryptionEnabledFields(\ReflectionClass $reflectionClass)
    {
        $encryptionEnabledFields = array();

        $reflectionProperties = $reflectionClass->getProperties();

        foreach ($reflectionProperties as $reflectionProperty) {
            $encryptedField = $this->reader->getPropertyAnnotation(
                $reflectionProperty,
                'EHEncryptionBundle\\Annotation\\EncryptedField'
            );

            if ($encryptedField) {
                $encryptionEnabledFields[] = $reflectionProperty;
            }
        }

        return $encryptionEnabledFields;
    }

    /**
     * Returns the value of an entity using reflection
     *
     * @param mixed $entity
     * @param \ReflectionProperty $reflectionProperty
     *
     * @return mixed
     */
    private function getFieldValue($entity, \ReflectionProperty $reflectionProperty)
    {
        $value = null;
        if ($reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($entity);
        }

        return $value;
    }

    /**
     * Sets the value of an entity using reflection
     *
     * @param mixed $entity
     * @param \ReflectionProperty $reflectionProperty
     * @param mixed $value
     */
    private function setFieldValue($entity, \ReflectionProperty $reflectionProperty, $value)
    {
        if ($reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->setValue($entity, $value);
        }
    }
}