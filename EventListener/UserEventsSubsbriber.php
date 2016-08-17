<?php

namespace EHEncryptionBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent as FOSFormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent as FOSFilterUserResponseEvent;
use EHEncryptionBundle\Service\EncryptionService;
use EHEncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use AppWebServiceBundle\Event as WebServiceEvent;
use PolavisConnectBundle\Event as PolavisConnectEvent;

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
            PolavisConnectEvent\Events::PC_USER_PRE_CREATE => 'onPolavisConnectUserPreCreate',
            PolavisConnectEvent\Events::PC_USER_POST_CREATE => 'onPolavisConnectUserPostCreate',
            PolavisConnectEvent\Events::PC_USER_RESETTING_RESET_SUCCESS => 'handlePolavisConnectPasswordResetSuccess',
        );

        return $events;
    }

    /**
     * Handles a successful password change using the FOSUserBundle forms
     *
     * @param \FOS\UserBundle\Event\FormEvent $event
     */
    public function handlePasswordChangeSuccess(FOSFormEvent $event)
    {
        $form = $event->getForm();
        $user = $form->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $currentPassword = $form->get('current_password')->getData();
            $this->encryptionService->handleUserPasswordChangeSuccess($user, $currentPassword);
        }
    }

    /**
     * Handles a successful password reset using the FOSUserBundle forms
     *
     * @param \FOS\UserBundle\Event\FormEvent $event
     */
    public function handlePasswordResetSuccess(FOSFormEvent $event)
    {
        $form = $event->getForm();
        $user = $form->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPasswordResetSuccess($user);
        }
    }

    /**
     * Handles a successful user registration using the FOSUserBundle forms
     *
     * @param \FOS\UserBundle\Event\FilterUserResponseEvent $event
     * @throws \Exception
     */
    public function handleUserRegistrationSuccess(FOSFormEvent $event)
    {
        $user = $event->getForm()->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPreCreation($user);
        }
    }

    /**
     * Handles the completion of a user registration using the FOSUserBundle forms
     *
     * @param \FOS\UserBundle\Event\FilterUserResponseEvent $event
     */
    public function handleUserRegistrationComplete(FOSFilterUserResponseEvent $event)
    {
        $user = $event->getUser();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPostCreation($user);
        }
    }

    /**
     * Handles a successful password reset through the Web Service
     *
     * @param \AppWebServiceBundle\Event\UserEvent $event
     */
    public function handleWebServicePasswordChangeSuccess(WebServiceEvent\UserEvent $event)
    {
        $user = $event->getUser();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            // By now in the app the current password is not sent when the user wants to
            // change his password, so we have to simulate a password reset
            $this->encryptionService->handleUserPasswordResetSuccess($user);
        }
    }

    /**
     * Handles the pre creation of a Polavis Connect User
     *
     * @param \PolavisConnectBundle\Event\UserEvent $event
     */
    public function onPolavisConnectUserPreCreate(PolavisConnectEvent\UserEvent $event)
    {
        $user = $event->getUser();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPreCreation($user);
        }
    }

    /**
     * Handles the post creation of a Polavis Connect User
     *
     * @param \PolavisConnectBundle\Event\UserEvent $event
     */
    public function onPolavisConnectUserPostCreate(PolavisConnectEvent\UserEvent $event)
    {
        $user = $event->getUser();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPostCreation($user);
        }
    }

    /**
     * Handles a successful password reset using the Polavis Connect forms
     *
     * @param \PolavisConnectBundle\Event\FormEvent $event
     */
    public function handlePolavisConnectPasswordResetSuccess(PolavisConnectEvent\FormEvent $event)
    {
        $form = $event->getForm();
        $user = $form->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPasswordResetSuccess($user);
        }
    }
}