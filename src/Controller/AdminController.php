<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/customers', name: 'admin_customers')]
    public function customers(): Response
    {
        
        $customers = [
            ['id' => 1, 'username' => '@kent', 'email' => 'kent@gmail.com', 'role' => 'Admin', 'bookings' => 0],
            ['id' => 2, 'username' => '@Pikachu', 'email' => 'pickachu@gmail.com', 'role' => 'Customer', 'bookings' => 0],
            ['id' => 3, 'username' => '@john', 'email' => 'jhon@gmail.com', 'role' => 'Customer', 'bookings' => 0],
            ['id' => 4, 'username' => '@user1', 'email' => 'user1@gmail.com', 'role' => 'Customer', 'bookings' => 1],
        ];

        return $this->render('admin/customers.html.twig', [
            'active_page' => 'customers',
            'customers' => $customers,
        ]);
    }

    #[Route('/admin/bookings', name: 'admin_bookings')]
    public function bookings(): Response
    {
        $bookings = [
            ['id' => 52, 'customer' => '@user1', 'device' => 'PC (2h)', 'tier' => 'BASIC', 'reserved_at' => 'Sep 16, 2026 07:50 PM', 'payment' => 'Pending (Cash at Counter)', 'status' => 'Completed']
        ];

        return $this->render('admin/bookings.html.twig', [
            'active_page' => 'bookings',
            'bookings' => $bookings,
        ]);
    }
}