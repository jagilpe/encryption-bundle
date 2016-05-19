<?php

namespace EHEncryptionBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Annotations\Reader;
use EHEncryptionBundle\Annotation\EncryptedEntity;
use EHEncryptionBundle\Crypt\CryptographyProviderInterface;
use EHEncryptionBundle\Crypt\KeyManagerInterface;

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
                    Reader $reader,
                    CryptographyProviderInterface $cryptographyProvider,
                    KeyManagerInterface $keyManager,
                    $settings)
    {
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
                    'type' => 'text',
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

        if ($isEncryptedEnabledUser = $this->isPKEncryptionEnabledUser($reflection)) {
            $publicKeyField = array(
                'fieldName' => 'publicKey',
                'columnName' => '_publicKey',
                'type' => 'text',
                'nullable' => true,
            );
            $metadata->mapField($publicKeyField);

            $privateKeyField = array(
                'fieldName' => 'privateKey',
                'columnName' => '_privateKey',
                'type' => 'text',
                'nullable' => true,
            );
            $metadata->mapField($privateKeyField);
        }

        return $metadata;
    }

    /**
     * Encrypts an entity is it has encryption enabled and it's not already encrypted
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    public function encryptEntity($entity)
    {
        return $this->processEntity($entity, self::ENCRYPT);
    }

    /**
     * Decrypts an entity is it has encryption enabled and it's encrypted
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    public function decryptEntity($entity)
    {
        return $this->processEntity($entity, self::DECRYPT);
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
                // Get the encryption key
                $key = $this->keyManager->getEntityEncryptionKey($entity);
                $iv = $this->keyManager->getEntityEncryptionIv($entity);

                // get the encrypted fields
                $encryptionEnabledFields = $this->getEncryptionEnabledFields($reflection);

                // Encrypt the fields
                foreach ($encryptionEnabledFields as $field) {
                    $value = $this->getFieldValue($entity, $field);
                    $encryptedValue = $this->cryptographyProvider->{$operation}($value, $key, $iv);
                    $this->setFieldValue($entity, $field, $encryptedValue);
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
        $encryptedEnabled = $this->reader->getClassAnnotation(
            $reflection,
            'EHEncryptionBundle\\Annotation\\PKEncryptionEnabledUser'
        );

        return $encryptedEnabled;
    }

    /**
     * Checks if the entity needs a field to store a key for each instance
     *
     * @param string $mode
     * @return boolean
     */
    private function keyPerEntityRequired(EncryptedEntity $hasEncryptionEnabled)
    {
        return $hasEncryptionEnabled->mode === self::MODE_PER_USER_SHAREABLE;
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