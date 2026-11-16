<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function index(
        Request $request,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();
            if (!is_string($currentPassword) || !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $passwordForm->get('currentPassword')->addError(new FormError('The current password is incorrect.'));
            } else {
                $newPassword = $passwordForm->get('newPassword')->getData();
                if (!is_string($newPassword) || $newPassword === '') {
                    throw new \LogicException('A valid password form must provide a new password.');
                }

                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->flush();

                $request->getSession()->invalidate();
                $this->addFlash('success', 'Your password has been changed. Please sign in again.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'bookings' => $bookingRepository->findByUser($user),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}
