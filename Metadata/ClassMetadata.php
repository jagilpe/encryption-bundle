<?php

namespace Module7\EncryptionBundle\Metadata;

use Metadata\MergeableClassMetadata;

/**
 * Class ClassMetadata
 * @package Module7\EncryptionBundle\Metadata
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class ClassMetadata  extends MergeableClassMetadata
{
    public $encryptionEnabled;

    public $encryptionMode;

    public $encryptedFile;

    public $encryptedFileMode;

    public function serialize()
    {
        $this->sortProperties();

        return serialize(array(
            $this->encryptionEnabled,
            $this->encryptionMode,
            $this->encryptedFile,
            $this->encryptedFileMode,
            parent::serialize(),
        ));
    }

    public function unserialize($str)
    {
        list(
            $this->encryptionEnabled,
            $this->encryptionMode,
            $this->encryptedFile,
            $this->encryptedFileMode,
            $parentStr
            ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}