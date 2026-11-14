<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            if ($user->isAdmin()) {
                return $this->redirectToRoute('admin_dashboard');
            }
            if ($user->isStaff()) {
                return $this->redirectToRoute('staff_dashboard');
            }

            return $this->redirectToRoute('app_home');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $error = null;

        if ($request->isMethod('POST')) {
            $identifier = trim($request->getPayload()->getString('identifier'));
            $email = trim($request->getPayload()->getString('email'));
            $newPassword = $request->getPayload()->getString('new_password');
            $confirmPassword = $request->getPayload()->getString('confirm_password');
            $csrfToken = $request->getPayload()->getString('_csrf_token');

            if (!$this->isCsrfTokenValid('forgot_password', $csrfToken)) {
                $error = 'Invalid security token. Please try again.';
            } elseif (empty($identifier) || empty($email) || empty($newPassword)) {
                $error = 'Please fill in all required fields.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                // Find matching user by username and email
                $user = $userRepository->findOneBy(['username' => $identifier, 'email' => $email]);

                // Also allow email in the identifier field
                if (!$user) {
                    $user = $userRepository->findOneBy(['email' => $identifier]);
                    if ($user && $user->getEmail() !== $email) {
                        $user = null;
                    }
                }

                if (!$user) {
                    $error = 'No matching account found with that username and email address.';
                } else {
                    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                    $entityManager->flush();

                    $this->addFlash('success', 'Your password has been reset successfully! Please sign in with your new password.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('auth/forgot_password.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
