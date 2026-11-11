<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET'])]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/register/process', name: 'app_register_process', methods: ['POST'])]
    public function registerProcess(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? $data['confirm'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            return new JsonResponse(['status' => 'error', 'message' => 'All fields are required.'], 400);
        }

        if ($password !== $confirmPassword) {
            return new JsonResponse(['status' => 'error', 'message' => 'Passwords do not match.'], 400);
        }

        if (strlen($password) < 6) {
            return new JsonResponse(['status' => 'error', 'message' => 'Password must be at least 6 characters.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Please enter a valid email address.'], 400);
        }

        // Check if username already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            return new JsonResponse(['status' => 'error', 'message' => 'Username is already taken.'], 400);
        }

        // Check if email already registered
        $existingEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingEmail) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email address is already registered.'], 400);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setRoles([User::ROLE_USER]);

        // Secure password hashing using Symfony's UserPasswordHasherInterface
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'status' => 'success', 
            'message' => 'Account created successfully! Redirecting to login...'
        ]);
    }
}
