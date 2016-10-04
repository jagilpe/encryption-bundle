<?php

namespace Module7\EncryptionBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent as FOSFormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent as FOSFilterUserResponseEvent;
use Module7\EncryptionBundle\Service\EncryptionService;
use Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

/**
 * Event subscriber for all the user related events for the encryption
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 */
class UserEventsSubsbriber implements EventSubscriberInterface
{
    /**
     * @var \Module7\EncryptionBundle\Service\EncryptionService
     */
    private $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * @TODO remove dependencies with Polavis Viva Bundles
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            FOSUserEvents::CHANGE_PASSWORD_SUCCESS => 'handlePasswordChangeSuccess',
            FOSUserEvents::RESETTING_RESET_SUCCESS => 'handlePasswordResetSuccess',
            FOSUserEvents::REGISTRATION_SUCCESS => 'handleUserRegistrationSuccess',
            FOSUserEvents::REGISTRATION_COMPLETED => 'handleUserRegistrationComplete',
        );

        if (class_exists(\AppWebServiceBundle\Event\Events::class)) {
            $events[\AppWebServiceBundle\Event\Events::PV_WS_PASSWORD_CHANGE_SUCCESS] = 'handleWebServicePasswordChangeSuccess';
        }

        if (class_exists(\PolavisConnectBundle\Event\Events::class)) {
            $events[\PolavisConnectBundle\Event\Events::PC_USER_PRE_CREATE] = 'onPolavisConnectUserPreCreate';
            $events[\PolavisConnectBundle\Event\Events::PC_USER_POST_CREATE] = 'onPolavisConnectUserPostCreate';
            $events[\PolavisConnectBundle\Event\Events::PC_USER_RESETTING_RESET_SUCCESS] = 'handlePolavisConnectPasswordResetSuccess';
        }

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
    public function handleWebServicePasswordChangeSuccess(\AppWebServiceBundle\Event\UserEvent $event)
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
    public function onPolavisConnectUserPreCreate(\PolavisConnectBundle\Event\UserEvent $event)
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
    public function onPolavisConnectUserPostCreate(\PolavisConnectBundle\Event\UserEvent $event)
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
    public function handlePolavisConnectPasswordResetSuccess(\PolavisConnectBundle\Event\FormEvent $event)
    {
        $form = $event->getForm();
        $user = $form->getData();

        if ($user instanceof PKEncryptionEnabledUserInterface) {
            $this->encryptionService->handleUserPasswordResetSuccess($user);
        }
    }
}