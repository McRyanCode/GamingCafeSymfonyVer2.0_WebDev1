<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;

class AuthController extends AbstractController
{
    private Connection $connection;

    public function connection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    // Constructor injection for Doctrine database connection
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function loginPage(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/login/process', name: 'app_login_process', methods: ['POST'])]
    public function loginProcess(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $loginInput = trim($data['login_input'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($loginInput) || empty($password)) {
            return new JsonResponse(["status" => "error", "message" => "Please enter both username/email and password."]);
        }

        $qb = $this->connection->createQueryBuilder();
        $user = $qb->select('*')
            ->from('users')
            ->where('username = :input OR email = :input')
            ->setParameter('input', $loginInput)
            ->setMaxResults(1)
            ->fetchAssociative();

        if ($user && password_verify($password, $user['password'])) {
            $request->getSession()->set('user_id', $user['id']);
            $request->getSession()->set('username', $user['username']);
            $request->getSession()->set('role', $user['role'] ?? 'user');

            return new JsonResponse([
                "status" => "success",
                "message" => "Login successful!",
                "username" => $user['username']
            ]);
        }

        return new JsonResponse(["status" => "error", "message" => "Incorrect username/email or password."]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET'])]
    public function registerPage(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/register/process', name: 'app_register_process', methods: ['POST'])]
    public function registerProcess(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirm = $data['confirm_password'] ?? $data['confirm'] ?? '';

        if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
            return new JsonResponse(["status" => "error", "message" => "All fields are required."]);
        }

        if (!preg_match('/[a-zA-Z]/', $username)) {
            return new JsonResponse(["status" => "error", "message" => "Username must contain at least one letter."]);
        }

        $emailParts = explode('@', $email);
        $emailPrefix = $emailParts[0] ?? '';
        $emailDomain = strtolower($emailParts[1] ?? '');

        if ($emailDomain !== 'gmail.com' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(["status" => "error", "message" => "Please use a valid Gmail address."]);
        }

        if (!preg_match('/[a-zA-Z]/', $emailPrefix)) {
            return new JsonResponse(["status" => "error", "message" => "Gmail address must contain at least one letter."]);
        }

        if (strlen($password) < 8) {
            return new JsonResponse(["status" => "error", "message" => "Password must be at least 8 characters."]);
        }

        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return new JsonResponse(["status" => "error", "message" => "Password must contain at least one letter and one number."]);
        }

        if ($password !== $confirm) {
            return new JsonResponse(["status" => "error", "message" => "Passwords do not match."]);
        }

        // Check existing user
        $existing = $this->connection->fetchAssociative(
            "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$username, $email]
        );

        if ($existing) {
            return new JsonResponse(["status" => "error", "message" => "Username or email is already taken/registered."]);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $inserted = $this->connection->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        if ($inserted) {
            return new JsonResponse(["status" => "success", "message" => "Registration successful! You can now log in."]);
        }

        return new JsonResponse(["status" => "error", "message" => "Registration failed. Try again."]);
    }
}