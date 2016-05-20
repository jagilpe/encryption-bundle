<?php

namespace EHEncryptionBundle\Event;

final class Events
{
    /**
     * This events is called before the PKI Keys of a user are generated
     */
    const PKI_KEY_PRE_GENERATE = 'eh_encription.pki_key.pre_generate';

    /**
     * This events is called after the PKI Keys of a user are generated
     */
    const PKI_KEY_POST_GENERATE = 'eh_encription.pki_key.post_generate';
}