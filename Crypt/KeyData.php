<?php

namespace EHEncryptionBundle\Crypt;

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