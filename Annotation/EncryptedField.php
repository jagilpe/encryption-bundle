<?php

namespace EHEncryptionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Defines an annotation whose goal configure the encryption for a determined field.
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class EncryptedField
{
    /**
     * If the encryption is enabled for the field
     */
    public $enabled = true;
}