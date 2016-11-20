<?php


namespace Module7\EncryptionBundle\Entity;

/**
 * Contract for all the encryptable file entities
 *
 * @package Module7\EncryptionBundle\Entity
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
interface EncryptableFile
{
    /**
     * Checks if the file is encrypted
     *
     * @return boolean
     */
    public function isFileEncrypted();

    /**
     * Returns the content of the file
     *
     * @return mixed
     */
    public function getContent();

    /**
     * Sets the content of the file
     *
     * @param mixed content
     */
    public function setContent($content);
}