<?php

namespace Jagilpe\EncryptionBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent as FOSFormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent as FOSFilterUserResponseEvent;
use Jagilpe\EncryptionBundle\Service\EncryptionService;
use Jagilpe\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;

/**
 * Event subscriber for all the user related events for the encryption
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 *
 */
class UserEventsSubsbriber implements EventSubscriberInterface
{
    /**
     * @var \Jagilpe\EncryptionBundle\Service\EncryptionService
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
     * @param FOSFormEvent $event
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
}