<?php

namespace EHEncryptionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use EHEncryptionBundle\Service\EncryptionService;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class EncryptedEntity
{
    public $enabled = true;

    public $mode = EncryptionService::MODE_PER_USER_SHAREABLE;
}
