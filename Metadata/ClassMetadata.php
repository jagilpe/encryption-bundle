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
    public $encryptionEnabled = false;

    public $encryptionMode;

    public $encryptedFile = false;

    public $encryptedFileMode;

    /**
     * @var ClassMetadata
     */
    public $parentClassMetadata;

    public function serialize()
    {
        $this->sortProperties();

        return serialize(array(
            $this->encryptionEnabled,
            $this->encryptionMode,
            $this->encryptedFile,
            $this->encryptedFileMode,
            $this->parentClassMetadata->serialize(),
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
            $parentClassMetadataString,
            $parentStr
            ) = unserialize($str);

        $this->parentClassMetadata = unserialize($parentClassMetadataString);
        parent::unserialize($parentStr);
    }
}