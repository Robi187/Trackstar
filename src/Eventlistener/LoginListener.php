<?php

namespace App\EventListener;

use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use App\Entity\User;

#[AsEventListener(event: CheckPassportEvent::class)]
class LoginListener
{
    public function __invoke(CheckPassportEvent $event): void
    {
        $user = $event->getPassport()->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->isBanned()) {
            $until = $user->getBannedUntil()->format('d.m.Y H:i');
            throw new CustomUserMessageAuthenticationException(
                "Dein Account ist gesperrt bis $until."
            );
        }
    }
}