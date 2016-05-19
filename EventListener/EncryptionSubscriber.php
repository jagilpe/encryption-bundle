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
    /**
     * @var EHEncryptionBundle\Service\EncryptionService
     */
    private $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::loadClassMetadata,
            Events::prePersist,
            Events::preUpdate,
            Events::postLoad,
        )
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
    public function prePersist(LifecycleEventArgs $args)
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