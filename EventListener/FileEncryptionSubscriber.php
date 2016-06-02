<?php

namespace EHEncryptionBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AppBundle\Event\Events;
use AppBundle\Event\FileEvent;
use EHEncryptionBundle\Service\EncryptionService;

/**
 * Subscriber for the encryption of files
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class FileEncryptionSubscriber implements EventSubscriberInterface
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
    public static function getSubscribedEvents()
    {
        return array(
            Events::PV_FILE_PRESAVE => 'onFilePreSave',
            Events::PV_FILE_LOAD => 'onFileLoad',
        );
    }

    public function onFilePreSave(FileEvent $event)
    {
        $fileEntity = $event->getFile();
    }

    public function onFileLoad(FileEvent $event)
    {
        $fileEntity = $event->getFile();

        if ($this->encryptionService->isEncryptableFile($fileEntity)
            && $this->encryptionService->isEntityEncrypted($fileEntity)) {

        }

    }
}