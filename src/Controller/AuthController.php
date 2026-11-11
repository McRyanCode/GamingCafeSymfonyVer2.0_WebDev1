<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;

class AuthController extends AbstractController
{
    public function __construct(private Connection $connection)
    {
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
}
