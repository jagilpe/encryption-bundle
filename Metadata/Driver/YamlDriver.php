<?php

namespace EHEncryptionBundle\Metadata\Driver;

use Metadata\Driver\AbstractFileDriver;

class YamlDriver extends AbstractFileDriver
{
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $metadata = null;

        return $metadata;
    }

    protected function getExtension()
    {
        return 'yml';
    }
}
