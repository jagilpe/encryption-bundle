<?php

namespace EHEncryptionBundle\Crypt\FieldEncrypter;

use EHEncryptionBundle\Crypt\KeyDataInterface;
use EHEncryptionBundle\Exception\EncryptionException;

class PrimitiveFieldEncrypter extends DefaultFieldEncrypter
{
    private $primitive;

    private static $allowedPrimitives = array(
        'boolean' => 'boolval',
        'double' => 'doubleval',
        'float' => 'floatval',
        'int' => 'intval',
        'str' => 'strval',
    );

    public function __construct($cryptographyProvider, $primitive)
    {
        parent::__construct($cryptographyProvider);
        if (!in_array($primitive, array_keys(static::$allowedPrimitives))) {
            throw new EncryptionException('Primitive not type '.$primitive.' not supported');
        }
        $this->primitive = $primitive;
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($clearValue, KeyDataInterface $keyData)
    {
        $stringValue = $clearValue !== null ? strval($clearValue) : null;
        return parent::encrypt($stringValue, $keyData);
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($encryptedValue, KeyDataInterface $keyData)
    {
        $primitiveValue = parent::decrypt($encryptedValue, $keyData);
        $primitiveConversor = static::$allowedPrimitives[$this->primitive];
        return $primitiveValue !== null ? call_user_func($primitiveConversor, $primitiveValue) : null;
    }
}