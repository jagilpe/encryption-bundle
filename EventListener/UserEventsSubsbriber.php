<?php

namespace EHEncryptionBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use EHEncryptionBundle\Service\EncryptionService;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

class UserEventsSubsbriber implements EventSubscriberInterface
{
    /**
     * @var \EHEncryptionBundle\Service\EncryptionService
     */
    private $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    public static function getSubscribedEvents()
    {
        $events = array(
            FOSUserEvents::CHANGE_PASSWORD_SUCCESS => 'handlePasswordChange',
            FOSUserEvents::REGISTRATION_SUCCESS => 'handleUserRegistration',
        );

        return $events;
    }

    public function handlePasswordChange(FormEvent $event)
    {

    }

    public function handleUserRegistration(FormEvent $event)
    {
        $user = $event->getForm()->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserRegistration($user);
        }
    }
}