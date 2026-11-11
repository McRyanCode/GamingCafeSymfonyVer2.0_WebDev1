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
    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/register/process', name: 'app_register_process', methods: ['POST'])]
    public function registerProcess(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            return new JsonResponse(['status' => 'error', 'message' => 'All fields are required.'], 400);
        }

        if ($password !== $confirmPassword) {
            return new JsonResponse(['status' => 'error', 'message' => 'Passwords do not match.'], 400);
        }

        // Check if user already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            return new JsonResponse(['status' => 'error', 'message' => 'Username is already taken.'], 400);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        
        // Hash the password securely
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'status' => 'success', 
            'message' => 'Account created successfully! Redirecting to login...'
        ]);
    }
}