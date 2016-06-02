<?php

namespace EHEncryptionBundle\Metadata;

use Metadata\ClassMetadata as BaseClassMetadata;
use EHEncryptionBundle\Service\EncryptionService;

/**
 * EHEncryption Class Metadata Class
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class ClassMetadata extends BaseClassMetadata
{
    const ACCESSOR_ORDER_UNDEFINED = 'undefined';
    const ACCESSOR_ORDER_ALPHABETICAL = 'alphabetical';
    const ACCESSOR_ORDER_CUSTOM = 'custom';

    public $enabled = false;
    public $mode = EncryptionService::MODE_PER_USER_SHAREABLE;
    public $encryptedFile = false;

    public function __construct(\ReflectionClass $reflection)
    {
        $this->name = $reflection->getName();

        $this->reflection = $reflection;
        $this->createdAt = time();
    }

    public function serialize()
    {
        $this->sortProperties();

        return serialize(array(
            $this->enabled,
            $this->mode,
            $this->encryptedFile,
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list(
            $this->enabled,
            $this->mode,
            $this->encryptedFile,
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }

    private function sortProperties()
    {
        switch ($this->accessorOrder) {
            case self::ACCESSOR_ORDER_ALPHABETICAL:
                ksort($this->propertyMetadata);
                break;

            case self::ACCESSOR_ORDER_CUSTOM:
                $order = $this->customOrder;
                uksort($this->propertyMetadata, function($a, $b) use ($order) {
                    $existsA = isset($order[$a]);
                    $existsB = isset($order[$b]);

                    if ( ! $existsA && ! $existsB) {
                        return 0;
                    }

                    if ( ! $existsA) {
                        return 1;
                    }

                    if ( ! $existsB) {
                        return -1;
                    }

                    return $order[$a] < $order[$b] ? -1 : 1;
                });
                    break;
        }
    }
}
