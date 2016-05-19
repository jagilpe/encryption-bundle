<?php

namespace EHEncryptionBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Annotations\Reader;
use EHEncryptionBundle\Annotation\EncryptedEntity;
use EHEncryptionBundle\Crypt\CryptographyProviderInterface;

/**
 * Encapsulates the core encryption logic
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class EncryptionService
{
    /**
     * Supported entity encryption modes
     */
    const MODE_PER_USER_SHAREABLE = 'PER_USER_SHAREABLE';

    private $reader;
    private $cryptographyProvider;

    public function __construct(Reader $reader, CryptographyProviderInterface $cryptographyProvider)
    {
        $this->reader = $reader;
        $this->cryptographyProvider = $cryptographyProvider;
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
        $reflection = new \ReflectionClass($entity);

        if ($this->hasEncryptionEnabled($reflection) && !$entity->isEncrypted()) {
            dump('encrypt');
            $entity->setEncrypted(true);
        }

        return $entity;
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
        $reflection = new \ReflectionClass($entity);

        if ($this->hasEncryptionEnabled($reflection) && $entity->isEncrypted()) {
            dump('decrypt');
            $entity->setEncrypted(false);
        }

        return $entity;
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
}