<?php

namespace EHEncryptionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class PKEncryptionEnabledUser
{
    public $enabled = true;
}
