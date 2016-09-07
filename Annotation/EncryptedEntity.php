<?php

namespace Module7\EncryptionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Module7\EncryptionBundle\Service\EncryptionService;

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

    public $mode = EncryptionService::MODE_PER_USER_SHAREABLE;
}
