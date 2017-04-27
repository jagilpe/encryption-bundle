<?php

namespace Jagilpe\EncryptionBundle\Crypt;

/**
 * Default implementation of the KeyData
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class KeyData implements KeyDataInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $iv;

    public function __construct($key, $iv)
    {
        $this->key = $key;
        $this->iv = $iv;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getIv()
    {
        return $this->iv;
    }
}