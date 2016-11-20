<?php

namespace Module7\EncryptionBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Module7\EncryptionBundle\Crypt\KeyData;
use Module7\EncryptionBundle\Metadata\ClassMetadata;
use Module7\EncryptionBundle\Metadata\ClassMetadataFactory;
use Module7\EncryptionBundle\Metadata\PropertyMetadata;
use Module7\EncryptionBundle\Crypt\CryptographyProviderInterface;
use Module7\EncryptionBundle\Crypt\KeyManagerInterface;
use Module7\EncryptionBundle\Crypt\FieldMapping;
use Module7\EncryptionBundle\Crypt\FieldEncrypter;
use Module7\EncryptionBundle\Crypt\FieldNormalizer;
use Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use Module7\EncryptionBundle\Exception\EncryptionException;

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
     * @var \Module7\EncryptionBundle\Crypt\CryptographyProviderInterface
     */
    private $cryptographyProvider;

    /**
     * @var \Module7\EncryptionBundle\Crypt\KeyManagerInterface
     */
    private $keyManager;

    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var array
     */
    private $encrypters = array();

    /**
     * @var array
     */
    private $encryptedEnabledClasses = array();

    /**
     * @var array
     */
    private $normalizers = array();

    public function __construct(
        Registry $doctrine,
        Reader $reader,
        CryptographyProviderInterface $cryptographyProvider,
        KeyManagerInterface $keyManager,
        ClassMetadataFactory $metadataFactory,
        $settings)
    {
        $this->doctrine = $doctrine;
        $this->reader = $reader;
        $this->cryptographyProvider = $cryptographyProvider;
        $this->keyManager = $keyManager;
        $this->metadataFactory = $metadataFactory;
        $this->settings = $settings;
    }

    /**
     * Returns the doctrine metadata of all the encryption enabled entities
     *
     * @return array
     */
    public function getEncryptionEnabledEntitiesMetadata()
    {
        $entityManager = $this->doctrine->getManager();
        $doctrineMetadataFactory = $entityManager->getMetadataFactory();

        $encryptedEnabledTypes = array();
        $encryptionMetadata = $this->metadataFactory->getAllMetadata();
        /** @var ClassMetadata $encryptionClassMetadata */
        foreach ($encryptionMetadata as $encryptionClassMetadata) {
            if ($encryptionClassMetadata->encryptionEnabled) {
                $className = $encryptionClassMetadata->name;
                $encryptedEnabledTypes[] = $doctrineMetadataFactory->getMetadataFor($className);
            }
        }

        return $encryptedEnabledTypes;
    }

    /**
     * Adds the metadata required to encrypt the doctrine entity
     *
     * @param ClassMetadataInfo $metadata
     * @return \Doctrine\ORM\Mapping\ClassMetadataInfo
     */
    public function addEncryptionMetadata(ClassMetadataInfo $metadata)
    {
        if ($metadata->isMappedSuperclass) {
            return;
        }

        $reflection = $metadata->getReflectionClass();
        if ($this->hasEncryptionEnabled($reflection)) {
            if ($this->keyPerEntityRequired($reflection)) {
                // Add the field required to hold the key used to encrypt this entity
                $keyField = array(
                    'fieldName' => 'key',
                    'columnName' => '_key',
                    'type' => 'object',
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

            // Field to force the persitence of the entity in the migration process
            $isMigratedField = array(
                'fieldName' => 'migrated',
                'columnName' => '_migrated',
                'type' => 'boolean',
            );
            $metadata->mapField($isMigratedField);

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

            // Add a field to check if the associated file is encrypted
            if ($this->hasFileEncryptionEnabled($reflection)) {
                $isFileEncryptedField = array(
                    'fieldName' => 'fileEncrypted',
                    'columnName' => '_file_encrypted',
                    'type' => 'boolean',
                );
                $metadata->mapField($isFileEncryptedField);
            }
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
    }

    /**
     * Process all encryption related actions on an Entity pre persist event
     *
     * @TODO remove Polavis Viva AppBundle dependency
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    public function processEntityPostPersist($entity)
    {
        $userClasses = $this->settings['user_classes'];
        foreach ($userClasses as $userClass) {
            if (class_exists($userClass) && $entity instanceof $userClass) {
                $relatedEntities = $this->getUserRelatedEntities($entity);
                foreach ($relatedEntities as $relatedEntity) {
                    $encryptionKey = $relatedEntity->getKey();
                    if ($encryptionKey) {
                        $encryptionKey->updateUnidentifiedKey($entity);
                    }
                }
            }
        }
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
        }
    }

    /**
     * Initializes user before is persisted
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function handleUserPreCreation(PKEncryptionEnabledUserInterface $user)
    {
        $this->keyManager->generateUserPKIKeys($user);
    }

    /**
     * Executes the required actions after the user is persisted
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function handleUserPostCreation(PKEncryptionEnabledUserInterface $user)
    {
        $this->keyManager->storeUserPKIKeys($user);
    }

    /**
     * Handles the event of a password change by the user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     * @param string $currentPassword
     */
    public function handleUserPasswordChangeSuccess(PKEncryptionEnabledUserInterface $user, $currentPassword)
    {
        $this->keyManager->handleUserPasswordChange($user, $currentPassword);
    }

    /**
     * Handles the event of a password reset by the user
     *
     * @param \Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface $user
     */
    public function handleUserPasswordResetSuccess(PKEncryptionEnabledUserInterface $user)
    {
        $this->keyManager->handleUserPasswordReset($user);
    }

    /**
     * Normalizes the data of the entity fields to the one required by the encryption
     * after the encryption has been activated
     *
     * @param mixed $entity
     */
    public function processEntityMigration($entity)
    {
        if ($entity) {
            $reflection = ClassUtils::newReflectionObject($entity);
            if ($this->hasEncryptionEnabled($reflection) && !$entity->isEncrypted()) {
                $encryptionEnabledFields = $this->getEncryptionEnabledFields($reflection);

                // Normalize the field
                foreach ($encryptionEnabledFields as $field) {
                    $fieldNormalizer = $this->getFieldNormalizer($field, $reflection);
                    $value = $this->getFieldValue($entity, $field);
                    $processedValue = $fieldNormalizer->normalize($value);
                    $this->setFieldValue($entity, $field, $processedValue);
                }

                $entity->setMigrated(true);
            }
        }
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
        $reflection = ClassUtils::newReflectionObject($entity);
        $classMetadata = $this->getEncryptionMetadataFor($reflection->getName());

        return $classMetadata->encryptionEnabled && $classMetadata->encryptedFile;
    }

    /**
     * Processes an entity if it has encryption enabled and it's not already processed
     *
     * @param mixed $entity
     * @param string $operation
     *
     * @return mixed
     */
    private function processEntity($entity, $operation)
    {
        if ($this->settings[$operation.'_on_backend']) {

            $reflection = ClassUtils::newReflectionObject($entity);
            $classMetadata = $this->getEncryptionMetadataFor($reflection->getName());
            if ($classMetadata->encryptionEnabled && $this->toProcess($entity, $operation)) {
                // Get the encryption key data
                $keyData = $this->keyManager->getEntityEncryptionKeyData($entity);
                if ($keyData) {
                    // get the encrypted fields
                    $encryptionEnabledFields = $this->getEncryptionEnabledFields($reflection);

                    // Encrypt the fields
                    foreach ($encryptionEnabledFields as $field) {
                        $fieldEncrypter = $this->getFieldEncrypter($field, $reflection);
                        $value = $this->getFieldValue($entity, $field);
                        $processedValue = $fieldEncrypter->{$operation}($value, $keyData);
                        $this->setFieldValue($entity, $field, $processedValue);
                    }

                    // Set the encryption flag
                    $entity->setEncrypted($operation === self::ENCRYPT);

                    // If this entity has an encryptable file, process it
                    if ($this->isEncryptableFile($entity) && $this->toProcessFile($entity, $operation)) {
                        $method = $operation.'File';
                        $this->{$method}($entity, $keyData);
                    }
                }
            }
        }
    }

    /**
     * Encrypts the uploaded file contained in a File Entity
     *
     * @param mixed $fileEntity
     * @param KeyData $keyData
     */
    private function encryptFile($fileEntity, KeyData $keyData)
    {
        $file = $fileEntity->getFile();
        $encrypt = false;

        if ($file) {
            $filePath = $file->getRealPath();
            $encrypt = true;
        }
        elseif ($fileEntity->getId() && $fileEntity->fileExists() && !$fileEntity->isFileEncrypted()) {
            // The document was persisted but somehow the file was not encrypted
            $filePath = $fileEntity->getAbsolutePath();
            $encrypt = true;
        }

        if ($encrypt) {
            $fileContent = file_get_contents($filePath);
            // Get the encryption key data
            if ($keyData) {
                $encType = CryptographyProviderInterface::FILE_ENCRYPTION;
                $encryptedContent = $this->cryptographyProvider->encrypt($fileContent, $keyData, $encType);

                // Replace the file content with the encrypted
                file_put_contents($filePath, $encryptedContent);
                $fileEntity->setFileEncrypted(true);
            }
        }
    }

    /**
     * Decrpyts the content of a file associated with an Encryptable File Entity
     *
     * @param mixed $fileEntity
     * @param KeyData $keyData
     */
    private function decryptFile($fileEntity, KeyData $keyData)
    {
        if ($keyData) {
            $encryptedContent = $fileEntity->getContent();
            if ($encryptedContent) {
                $encType = CryptographyProviderInterface::FILE_ENCRYPTION;
                $decryptedContent = $this->cryptographyProvider->decrypt($encryptedContent, $keyData, $encType);

                $fileEntity->setContent($decryptedContent);
            }
        }
    }

    /**
     * Checks if the entity has to be processed
     *
     * @param mixed $entity
     * @param string $operation
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
     * @param string $operation
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
     * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
     *
     * @return boolean
     */
    public function hasEncryptionEnabled(\ReflectionClass $reflection, ClassMetadataInfo $metadata = null)
    {
        $classMetadata = $this->getEncryptionMetadataFor($reflection->getName());
        return $classMetadata->encryptionEnabled;
    }

    /**
     * Checks if the class is a File entity and has the file encryption enabled
     *
     * @param \ReflectionClass $reflection
     * @return boolean
     */
    private function hasFileEncryptionEnabled(\ReflectionClass $reflection)
    {
        $classMetadata = $this->getEncryptionMetadataFor($reflection->getName());
        return $classMetadata->encryptionEnabled && $classMetadata->encryptedFile;
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
     * @param \ReflectionClass $classMetadata
     *
     * @return boolean
     */
    private function keyPerEntityRequired(\ReflectionClass $classMetadata)
    {
        return $this->userBasedEncryption();
    }

    /**
     * Checks the fields of the entity and returns a list of those with encryption enabled
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    public function getEncryptionEnabledFields(\ReflectionClass $reflectionClass)
    {
        $encryptionEnabledFields = array();

        $classMetadata = $this->getEncryptionMetadataFor($reflectionClass->getName());

        if ($classMetadata->encryptionEnabled) {
            /** @var PropertyMetadata $propertyMetadata */
            foreach ($classMetadata->propertyMetadata as $propertyMetadata) {
                if ($propertyMetadata->encrypted) {
                    $reflectionProperty = $reflectionClass->getProperty($propertyMetadata->name);
                    $encryptionEnabledFields[$reflectionProperty->name] = $reflectionProperty;
                }
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
     * @return \Module7\EncryptionBundle\Crypt\FieldMapping\EncryptedFieldMappingInterface
     *
     * @throws EncryptionException
     */
    private function getEncryptedFieldMapping(array $fieldMapping)
    {
        switch($fieldMapping['type']) {
            case 'string':
                return new FieldMapping\StringFieldMapping($this, $fieldMapping);
            case 'text':
            case 'json_array':
            case 'simple_array':
                return new FieldMapping\TextFieldMapping($this, $fieldMapping);
            case 'date':
            case 'datetime':
                return new FieldMapping\DateTimeFieldMapping($this, $fieldMapping);
            case 'boolean':
            case 'smallint':
            case 'integer':
            case 'bigint':
            case 'float':
                return new FieldMapping\PrimitiveFieldMapping($this, $fieldMapping);
            default:
                throw new EncryptionException('Field type '.$fieldMapping['type'].' not supported.');
        }
    }

    /**
     * Factory method to get the right EncryptedFieldEncrypter object for a determined field
     *
     * @param \ReflectionProperty $reflectionProperty
     * @param \ReflectionClass $reflectionClass
     *
     * @return \Module7\EncryptionBundle\Crypt\FieldEncrypter\EncryptedFieldEncrypterInterface
     *
     * @throws EncryptionException
     */
    private function getFieldEncrypter(\ReflectionProperty $reflectionProperty, \ReflectionClass $reflectionClass)
    {
        $classMetadata = $this->doctrine->getManager()->getMetadataFactory()->getMetadataFor($reflectionClass->getName());

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
            case 'json_array':
            case 'simple_array':
                $encrypterClass = FieldEncrypter\SerializableObjectFieldEncrypter::class;
                break;
            case 'boolean':
            case 'smallint':
            case 'integer':
            case 'bigint':
            case 'float':
                $encrypterClass = FieldEncrypter\PrimitiveFieldEncrypter::class;
                $fieldType = $fieldMapping['_old_type'];
                if (!isset($this->encrypters[$encrypterClass][$fieldType])) {
                    $this->encrypters[$encrypterClass][$fieldType] =
                        new $encrypterClass($this->cryptographyProvider, $fieldType);
                }
                return $this->encrypters[$encrypterClass][$fieldType];
                break;
            default:
                throw new EncryptionException('Field type '.$fieldMapping['_old_type'].' not supported.');
        }

        if (!isset($this->encrypters[$encrypterClass])) {
            $this->encrypters[$encrypterClass] = new $encrypterClass($this->cryptographyProvider);
        }

        return $this->encrypters[$encrypterClass];
    }

    /**
     * Factory method to get the right EncryptedFieldNormalizer object for a determined field
     *
     * @param \ReflectionProperty $reflectionProperty
     * @param \ReflectionClass $reflectionClass
     *
     * @return \Module7\EncryptionBundle\Crypt\FieldNormalizer\EncryptedFieldNormalizerInterface
     *
     * @throws EncryptionException
     */
    private function getFieldNormalizer(\ReflectionProperty $reflectionProperty, \ReflectionClass $reflectionClass)
    {
        $classMetadata = $this->doctrine->getManager()->getMetadataFactory()->getMetadataFor($reflectionClass->getName());

        $fieldName = $reflectionProperty->getName();
        $fieldMapping = $classMetadata->getFieldMapping($fieldName);

        if (!isset($fieldMapping['_old_type'])) {
            throw new EncryptionException('Field metadata not updated');
        }

        switch ($fieldMapping['_old_type']) {
            case 'string':
            case 'text':
                $normalizerClass = FieldNormalizer\DefaultFieldNormalizer::class;
                break;
            case 'date':
            case 'datetime':
                $normalizerClass = FieldNormalizer\DateTimeFieldNormalizer::class;
                break;
            case 'json_array':
                $normalizerClass = FieldNormalizer\JsonArrayFieldNormalizer::class;
                break;
            case 'simple_array':
                $normalizerClass = FieldNormalizer\SimpleArrayFieldNormalizer::class;
                break;
            case 'boolean':
            case 'smallint':
            case 'integer':
            case 'bigint':
            case 'float':
                $normalizerClass = FieldNormalizer\PrimitiveFieldNormalizer::class;
                $fieldType = $fieldMapping['_old_type'];
                if (!isset($this->normalizers[$normalizerClass][$fieldType])) {
                    $this->normalizers[$normalizerClass][$fieldType] =
                    new $normalizerClass($fieldType);
                }
                return $this->normalizers[$normalizerClass][$fieldType];
                break;
            default:
                throw new EncryptionException('Field type '.$fieldMapping['_old_type'].' not supported.');
        }

        if (!isset($this->normalizers[$normalizerClass])) {
            $this->normalizers[$normalizerClass] = new $normalizerClass();
        }

        return $this->normalizers[$normalizerClass];
    }

    /**
     * Returns the entities that are related with the user entity and can be persisted
     * in the same moment as the user entity. For this entities the user id is not set
     * in the moment they are persisted and therefore not saved with the key
     *
     * @param mixed $user
     *
     * @return array
     */
    private function getUserRelatedEntities($user)
    {
        // The id of the User was not set when the entity was processed before
        $userProfile = $user->getMainProfile();
        return $userProfile ? array($userProfile) : null;
    }

    /**
     * Returns the encryption metadata for the given class
     *
     * @param $className
     *
     * @return ClassMetadata
     */
    private function getEncryptionMetadataFor($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }
}