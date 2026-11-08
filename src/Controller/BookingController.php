<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    #[Route('/booking', name: 'app_booking')]
    public function index(): Response
    {
        $pcs = [
            ['id' => 'PC-01', 'name' => 'PC 01', 'img' => 'pc.png', 'gpu' => 'RTX 4070', 'cpu' => 'i7-13700K', 'hz' => '240Hz'],
            ['id' => 'PC-02', 'name' => 'PC 02', 'img' => 'pc2.png', 'gpu' => 'RTX 4070', 'cpu' => 'i7-13700K', 'hz' => '240Hz'],
            ['id' => 'PC-03', 'name' => 'PC 03', 'img' => 'ps3.png', 'gpu' => 'RTX 3060', 'cpu' => 'i5-12400F', 'hz' => '144Hz'],
            ['id' => 'PC-04', 'name' => 'PC 04', 'img' => 'pc4.png', 'gpu' => 'RTX 3060', 'cpu' => 'i5-12400F', 'hz' => '144Hz'],
            ['id' => 'PC-05', 'name' => 'PC 05', 'img' => 'pc.png', 'gpu' => 'RTX 3060', 'cpu' => 'i5-12400F', 'hz' => '144Hz'],
            ['id' => 'PC-06', 'name' => 'PC 06', 'img' => 'pc7.png', 'gpu' => 'RTX 3060', 'cpu' => 'i5-12400F', 'hz' => '144Hz'],
        ];

        $consoles = [
            ['id' => 'Console-01', 'name' => 'Console 01', 'img' => 'ps5.png', 'spec1' => 'PS5', 'spec2' => '4K OLED TV', 'spec3' => '2 Controllers'],
            ['id' => 'Console-02', 'name' => 'Console 02', 'img' => 'swtch.png', 'spec1' => 'SWITCH 2', 'spec2' => '4K OLED TV', 'spec3' => '2 Controllers'],
            ['id' => 'Console-03', 'name' => 'Console 03', 'img' => 'Xbox.png', 'spec1' => 'Xbox Series X', 'spec2' => '4K TV', 'spec3' => '2 Controllers'],
        ];

        return $this->render('booking/index.html.twig', [
            'pcs' => $pcs,
            'consoles' => $consoles,
        ]);
    }

    #[Route('/booking/process', name: 'app_booking_process', methods: ['POST'])]
    public function process(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['device_id'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid booking data provided.'
            ], 400);
        }

        // Mock saving to database and generating a booking ID
        $bookingId = rand(100, 999);

        return new JsonResponse([
            'success' => true,
            'booking_id' => $bookingId,
            'device_id' => $data['device_id'],
            'device_type' => $data['device_type'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'duration' => $data['time_mode'] === 'Open' ? 'Open Time' : $data['hours'] . ' Hour(s) (Fixed)',
            'total_price' => $data['time_mode'] === 'Open' ? '₱40 (Min Deposit)' : '₱' . ($data['hours'] * 40),
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'],
        ]);
    }
}