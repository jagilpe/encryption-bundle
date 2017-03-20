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
     * Returns the id of the entity
     *
     * @return integer
     */
    public function getId();

    /**
     * Checks if the file is encrypted
     *
     * @return boolean
     */
    public function isFileEncrypted();

    /**
     * Sets the file as encrypted
     *
     * @param boolean $encrypted
     */
    public function setFileEncrypted($encrypted);

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

    /**
     * Sets the content of the file
     *
     * @return \SplFileInfo
     */
    public function getFile();

    /**
     * Checks if the file actually exists
     *
     * @return boolean
     */
    public function fileExists();

    /**
     * Returns the absolute path of the file
     *
     * @return string
     */
    public function getAbsolutePath();
}