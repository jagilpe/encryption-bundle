<?php

namespace Jagilpe\EncryptionBundle\Crypt\FieldEncrypter;

use Jagilpe\EncryptionBundle\Crypt\KeyDataInterface;
use Jagilpe\EncryptionBundle\Exception\EncryptionException;

/**
 * Implementation of the FieldEncrypterInterface for primitive values other than the text ones
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class PrimitiveFieldEncrypter extends DefaultFieldEncrypter
{
    private $primitive;

    private static $allowedPrimitives = array(
        'boolean' => 'boolval',
        'smallint' => 'intval',
        'integer' => 'intval',
        'bigint' => 'intval',
        'float' => 'floatval',
    );

    public function __construct($cryptographyProvider, $primitive)
    {
        parent::__construct($cryptographyProvider);
        if (!in_array($primitive, array_keys(static::$allowedPrimitives))) {
            throw new EncryptionException('Primitive type '.$primitive.' not supported');
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
        $stringValue = parent::decrypt($encryptedValue, $keyData);
        $primitiveConversor = static::$allowedPrimitives[$this->primitive];
        $primivitveValue = $stringValue !== null ? call_user_func($primitiveConversor, $stringValue) : null;
        return $primivitveValue;
    }
}