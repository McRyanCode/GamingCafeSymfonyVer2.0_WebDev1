<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RecordVoter extends Voter
{
    public const EDIT = 'RECORD_EDIT';
    public const DELETE = 'RECORD_DELETE';
    public const VIEW = 'RECORD_VIEW';

    public function __construct(
        private AuthorizationCheckerInterface $security
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW], true)
            && $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Booking $booking */
        $booking = $subject;

        // Admins have full access to view, edit, and delete all records
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Staff can view records
        if ($attribute === self::VIEW && $this->security->isGranted('ROLE_STAFF')) {
            return true;
        }

        // Staff can ONLY edit or delete records they created themselves
        // Cannot edit admin records or other staff records
        if (in_array($attribute, [self::EDIT, self::DELETE], true)) {
            if ($this->security->isGranted('ROLE_STAFF')) {
                return $booking->getUser() !== null && $booking->getUser()->getId() === $user->getId();
            }
        }

        // Regular users can only view their own records
        if ($attribute === self::VIEW) {
            return $booking->getUser() !== null && $booking->getUser()->getId() === $user->getId();
        }

        return false;
    }
}
