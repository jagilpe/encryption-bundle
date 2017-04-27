<?php

namespace Module7\EncryptionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Annotation to mark an entity as encryptable
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 * @Annotation
 * @Target("CLASS")
 */
final class EncryptedEntity
{
    public $enabled = true;

    public $mode;
}
