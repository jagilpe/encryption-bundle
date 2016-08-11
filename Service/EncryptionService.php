<?php

namespace EHEncryptionBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Bundle\DoctrineBundle\Registry;
use EHEncryptionBundle\Annotation\EncryptedEntity;
use EHEncryptionBundle\Crypt\CryptographyProviderInterface;
use EHEncryptionBundle\Crypt\KeyManagerInterface;
use EHEncryptionBundle\Crypt\FieldMapping;
use EHEncryptionBundle\Crypt\FieldEncrypter;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use EHEncryptionBundle\Exception\EncryptionException;

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
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $reader;

    /**
     * @var \EHEncryptionBundle\Crypt\CryptographyProviderInterface
     */
    private $cryptographyProvider;

    /**
     * @var \EHEncryptionBundle\Crypt\KeyManagerInterface
     */
    private $keyManager;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var array
     */
    private $fieldEncrypters;

    public function __construct(
                    Registry $doctrine,
                    Reader $reader,
                    CryptographyProviderInterface $cryptographyProvider,
                    KeyManagerInterface $keyManager,
                    $settings)
    {
        $this->doctrine = $doctrine;
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

            // Field to control is the entity is already encrypted or not
            $isEncryptedField = array(
                'fieldName' => 'encrypted',
                'columnName' => '_encrypted',
                'type' => 'boolean',
            );
            $metadata->mapField($isEncryptedField);

            // Modify the metadata of the encrypted fields of the entity
            $encryptedFields = $this->getEncryptionEnabledFields($reflection);
            foreach ($encryptedFields as $encryptedField) {
                $fieldName = $encryptedField->name;
                $fieldMapping = $metadata->getFieldMapping($fieldName);
                $encryptedFieldMapping = $this->getEncryptedFieldMapping($fieldMapping);
                $override = $encryptedFieldMapping->getMappingAttributeOverride();
                /*
                 * It's not possible to change the type of a column using
                 * Doctrine\ORM\Mapping\ClassMetadataInfo::setAssociationOverride
                 * The only alternative that I found it to directly access the fieldMappings property
                 * that until the version 2.5 of Doctrine ORM is public. If this changes in comming
                 * versions of Doctrine this should also be changed
                 */
                $metadata->fieldMappings[$fieldName] = $override;
            }
        }

        // Add a field to check if the associated file is encrypted
        if ($hasFileEncryptionEnabled = $this->hasFileEncryptionEnabled($reflection)) {
            $isFileEncryptedField = array(
                'fieldName' => 'fileEncrypted',
                'columnName' => '_file_encrypted',
                'type' => 'boolean',
            );
            $metadata->mapField($isFileEncryptedField);
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
        // Process the encryption of the entity
        $this->processEntity($entity, self::ENCRYPT);

        // Process the possible file associated with the entity
        $this->processFileEntity($entity, self::ENCRYPT);
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
        if ($entity) {
            // Process the encryption of the entity
            $this->processEntity($entity, self::DECRYPT);

            $this->processFileEntity($entity, self::DECRYPT);
        }
    }

    /**
     * Initializes the registrered user to use the encryption
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function handleUserRegistrationSuccess(PKEncryptionEnabledUserInterface $user)
    {
        $this->keyManager->generateUserPKIKeys($user);
    }

    /**
     * Executes the required actions after the registration of user is completed
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function handleUserRegistrationComplete(PKEncryptionEnabledUserInterface $user)
    {
        $this->keyManager->storeUserPKIKeys($user);
    }

    /**
     * Handles the event of a password change by the user
     *
     * @param \EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @param string $currenctPassword
     */
    public function handleUserPasswordChangeSuccess(PKEncryptionEnabledUserInterface $user, $currentPassword)
    {
        $this->keyManager->handleUserPasswordChange($user, $currentPassword);
    }

    /**
     * Checks if the entity has file encryption enabled and the file is actually encrypted
     *
     * @param mixed $entity
     *
     * @return boolean
     */
    private function isEntityFileEncrypted($entity)
    {
        $reflection = new \ReflectionClass($entity);

        return $this->hasFileEncryptionEnabled($reflection)
        && $reflection->hasMethod('isFileEncrypted')
        && $entity->isFileEncrypted();
    }

    /**
     * Checks if the entity has file encryption enabled
     *
     * @param mixed $entity
     *
     * @return boolean
     */
    private function isEncryptableFile($entity)
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
                    $fieldEncrypter = $this->getFieldEncrypter($field);
                    $value = $this->getFieldValue($entity, $field);
                    $processedValue = $fieldEncrypter->{$operation}($value, $keyData);
                    $this->setFieldValue($entity, $field, $processedValue);
                }

                // Set the encryption flag
                $entity->setEncrypted($operation === self::ENCRYPT);
            }
        }

        return $entity;
    }

    /**
     * Processes an entity is it has file encryption enabled and it's not already processed
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    private function processFileEntity($entity, $operation)
    {
        if ($this->settings[$operation.'_on_backend']) {
            $reflection = new \ReflectionClass($entity);

            if ($this->hasFileEncryptionEnabled($reflection) && $this->toProcessFile($entity, $operation)) {
                switch ($operation) {
                    case self::ENCRYPT:
                        $this->encryptFile($entity);
                        break;
                    case self::DECRYPT:
                        $this->decryptFile($entity);
                        break;
                    default:
                        throw new EncryptionException('Operation '.$operation.' not supported.');
                        break;
                }
            }
        }

        return $entity;
    }

    /**
     * Encrypts the uploaded file contained in a File Entity
     *
     * @param mixed $entity
     */
    private function encryptFile($entity)
    {
        $file = $entity->getFile();

        if ($file) {
            $filePath = $file->getRealPath();
            $fileContent = file_get_contents($filePath);
            // Get the encryption key data
            $keyData = $this->keyManager->getEntityEncryptionKeyData($entity);

            $encType = CryptographyProviderInterface::FILE_ENCRYPTION;
            $encryptedContent = $this->cryptographyProvider->encrypt($fileContent, $keyData, $encType);

            // Replace the file content with the encrypted
            file_put_contents($filePath, $encryptedContent);
            $entity->setFileEncrypted(true);
        }
    }

    /**
     * Decrpyts the content of a file associated with an Encryptable File Entity
     *
     * @param mixed $entity
     */
    private function decryptFile($fileEntity)
    {
        // Get the encryption key data
        $keyData = $this->keyManager->getEntityEncryptionKeyData($fileEntity);

        $encryptedContent = $fileEntity->getContent();
        $encType = CryptographyProviderInterface::FILE_ENCRYPTION;
        $decryptedContent = $this->cryptographyProvider->decrypt($encryptedContent, $keyData, $encType);

        $fileEntity->setContent($decryptedContent);
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
     * Checks if the entity has to be processed
     *
     * @param mixed $entity
     * @param unknown $operation
     *
     * @return boolean
     */
    private function toProcessFile($entity, $operation)
    {
        switch ($operation) {
            case self::ENCRYPT:
                return !$entity->isFileEncrypted();
            case self::DECRYPT:
                return $entity->isFileEncrypted();
            default:
                return false;
        }
    }

    /**
     * Checks if the class has been enabled for encryption
     *
     * @param \ReflectionClass $reflection
     * @return EHEncryptionBundle\Annotation\EncryptedEntity|null
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
     * Checks if the class is a File entity and has the file encryption enabled
     *
     * @param \ReflectionClass $reflection
     * @return EHEncryptionBundle\Annotation\EncryptedFile|null
     */
    private function hasFileEncryptionEnabled(\ReflectionClass $reflection)
    {
        $fileEncryptionEnabled = $this->reader->getClassAnnotation(
            $reflection,
            'EHEncryptionBundle\\Annotation\\EncryptedFile'
        );

        return $fileEncryptionEnabled;
    }

    /**
     * Checks if the class represents a user with public key encryption enabled
     *
     * @param mixed $entity
     *
     * @return EHEncryptionBundle\Annotation\PKEncryptionEnabledUser / null
     */
    private function isPKEncryptionEnabledUser($entity)
    {
        $reflectionClass = new \ReflectionClass($entity);
        return $this->userBasedEncryption()
            && ($reflectionClass->implementsInterface(PKEncryptionEnabledUserInterface::class));
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

    /**
     * Factory method to get the right EncryptedFieldMapping object for a determined field
     *
     * @param array $fieldMapping
     *
     * @return \EHEncryptionBundle\Crypt\FieldMapping\EncryptedFieldMappingInterface
     */
    private function getEncryptedFieldMapping(array $fieldMapping)
    {
        switch($fieldMapping['type']) {
            case 'string':
                return new FieldMapping\StringFieldMapping($this, $fieldMapping);
            case 'text':
                return new FieldMapping\TextFieldMapping($this, $fieldMapping);
            case 'date':
            case 'datetime':
                return new FieldMapping\DateTimeFieldMapping($this, $fieldMapping);
            default:
                throw new EncryptionException('Field type '.$fieldMapping['type'].' not supported.');
        }
    }

    /**
     * Factory method to get the right EncryptedFieldEncrypter object for a determined field
     *
     * @param array $fieldMapping
     *
     * @return \EHEncryptionBundle\Crypt\FieldMapping\EncryptedFieldMappingInterface
     */
    private function getFieldEncrypter(\ReflectionProperty $reflectionProperty)
    {
        $classMetadata = $this->doctrine->getManager()->getMetadataFactory()->getMetadataFor($reflectionProperty->class);

        $fieldName = $reflectionProperty->getName();
        $fieldMapping = $classMetadata->getFieldMapping($fieldName);

        if (!isset($fieldMapping['_old_type'])) {
            throw new EncryptionException('Field metadata not updated');
        }

        switch ($fieldMapping['_old_type']) {
            case 'string':
            case 'text':
                $encrypterClass = FieldEncrypter\DefaultFieldEncrypter::class;
                break;
            case 'date':
            case 'datetime':
                $encrypterClass = FieldEncrypter\SerializableObjectFieldEncrypter::class;
                break;
            default:
                throw new EncryptionException('Field type '.$fieldMapping['_old_type'].' not supported.');
        }

        if (!isset($this->encrypters[$encrypterClass])) {
            $this->encrypters[$encrypterClass] = new $encrypterClass($this->cryptographyProvider);
        }

        return $this->encrypters[$encrypterClass];
    }
}