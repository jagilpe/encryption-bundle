<?php

namespace EHEncryptionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class EncryptedFile
{
    public $enabled = true;

    public $mode;
}
