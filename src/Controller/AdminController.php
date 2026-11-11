<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin/customers', name: 'admin_customers')]
    public function customers(UserRepository $userRepository): Response
    {
        // Fetch all users/customers live from your MySQL database
        $users = $userRepository->findAll();

        return $this->render('admin/customers.html.twig', [
            'users' => $users,
        ]);
    }
}