<?php

namespace EHEncryptionBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use EHEncryptionBundle\Service\EncryptionService;

/**
 * Event subscriber for all the doctrine related events for the encryption
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 */
class EncryptionSubscriber implements EventSubscriber
{
    private $encryptionService;
    private $encryptionEnabled;

    public function __construct(EncryptionService $encryptionService, $encryptionEnabled)
    {
        $this->encryptionService = $encryptionService;
        $this->encryptionEnabled = $encryptionEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return $this->encryptionEnabled
        ? array(
            Events::loadClassMetadata,
            Events::prePersist,
            Events::preUpdate,
            Events::postLoad,
        )
        : array()
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        // We let the Encryption service add the required metadata to the entity
        $this->encryptionService->addEncryptionMetadata($eventArgs->getClassMetadata());
    }

    /**
     * {@inheritDoc}
     */
    public function prePresists(LifecycleEventArgs $args)
    {
        $this->encryptionService->encryptEntity($args->getEntity());
    }

    /**
     * {@inheritDoc}
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->encryptionService->encryptEntity($args->getEntity());
    }

    /**
     * {@inheritDoc}
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $this->encryptionService->decryptEntity($args->getEntity());
    }
}