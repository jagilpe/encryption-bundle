<?php

namespace EHEncryptionBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use EHEncryptionBundle\Service\EncryptionService;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use AppWebServiceBundle\Event as WebServiceEvent;

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
            FOSUserEvents::CHANGE_PASSWORD_SUCCESS => 'handlePasswordChangeSuccess',
            FOSUserEvents::RESETTING_RESET_SUCCESS => 'handlePasswordResetSuccess',
            FOSUserEvents::REGISTRATION_SUCCESS => 'handleUserRegistrationSuccess',
            FOSUserEvents::REGISTRATION_COMPLETED => 'handleUserRegistrationComplete',
            WebServiceEvent\Events::PV_WS_PASSWORD_CHANGE_SUCCESS => 'handleWebServicePasswordChangeSuccess',
        );

        return $events;
    }

    public function handlePasswordChangeSuccess(FormEvent $event)
    {
        $form = $event->getForm();
        $user = $form->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $currentPassword = $form->get('current_password')->getData();
            $this->encryptionService->handleUserPasswordChangeSuccess($user, $currentPassword);
        }
    }

    public function handlePasswordResetSuccess(FormEvent $event)
    {
        $form = $event->getForm();
        $user = $form->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPasswordResetSuccess($user);
        }
    }

    public function handleUserRegistrationSuccess(FormEvent $event)
    {
        $user = $event->getForm()->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserRegistrationSuccess($user);
        }
    }

    public function handleUserRegistrationComplete(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserRegistrationComplete($user);
        }
    }

    public function handleWebServicePasswordChangeSuccess(WebServiceEvent\UserEvent $event)
    {
        $user = $event->getUser();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            // By now in the app the current password is not sent when the user wants to
            // change his password, so we have to simulate a password reset
            $this->encryptionService->handleUserPasswordResetSuccess($user);
        }
    }
}