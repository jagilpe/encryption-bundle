<?php

namespace Jagilpe\EncryptionBundle\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * Class PropertyMetadata
 * @package Jagilpe\EncryptionBundle\Metadata
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class PropertyMetadata extends BasePropertyMetadata
{
    public $encrypted;

    public function serialize()
    {
        return serialize(array(
            $this->encrypted,
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list(
            $this->encrypted,
            $parentStr
        ) = $this->unserialize($str);

        parent::unserialize($parentStr);
    }
}