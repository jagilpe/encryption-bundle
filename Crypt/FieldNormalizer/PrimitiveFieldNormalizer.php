<?php

namespace Jagilpe\EncryptionBundle\Crypt\FieldNormalizer;

use Jagilpe\EncryptionBundle\Exception\EncryptionException;

/**
 * Implementation of the EncryptedFieldNormalizerInterface for primitive fields other than the text ones
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class PrimitiveFieldNormalizer extends DefaultFieldNormalizer
{
    private $primitive;

    private static $allowedPrimitives = array(
        'boolean' => 'boolval',
        'smallint' => 'intval',
        'integer' => 'intval',
        'bigint' => 'intval',
        'float' => 'floatval',
    );

    public function __construct($primitive)
    {
        parent::__construct();
        if (!in_array($primitive, array_keys(static::$allowedPrimitives))) {
            throw new EncryptionException('Primitive type '.$primitive.' not supported');
        }
        $this->primitive = $primitive;
    }

    /**
     * {@inheritDoc}
     * @see \Jagilpe\EncryptionBundle\Crypt\FieldNormalizer\DefaultFieldNormalizer::normalize()
     */
    public function normalize($clearValue)
    {
        $normalizedValue = null;

        if ($clearValue !== null) {
            $primitiveConversor = static::$allowedPrimitives[$this->primitive];
            $primivitveValue = $clearValue !== null ? call_user_func($primitiveConversor, $clearValue) : null;
            $normalizedValue = $primivitveValue;
        }

        return $normalizedValue;
    }
}