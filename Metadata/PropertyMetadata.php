<?php

namespace EHEncryptionBundle\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * Property metadata class
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class PropertyMetadata extends BasePropertyMetadata
{
    public function serialize()
    {
        return serialize(array(
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list(
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}
