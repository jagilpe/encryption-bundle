<?php

namespace EHEncryptionBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PKIKeyGenerationEvent extends Event
{
    private $user;

    private $previousKeys;

    public function __construct($user, $previousKeys = null)
    {
        $this->user = $user;
        $this->previousKeys = $previousKeys;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPreviousKeys()
    {
        return $this->previousKeys;
    }
}