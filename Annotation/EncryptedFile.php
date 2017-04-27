<?php

namespace Jagilpe\EncryptionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Jagilpe\EncryptionBundle\Service\EncryptionService;

/**
 * Annotation to mark an entity as an encryptable file
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 * @Annotation
 * @Target("CLASS")
 */
final class EncryptedFile
{
    public $enabled = true;

    public $mode = EncryptionService::MODE_PER_USER_SHAREABLE;
}
