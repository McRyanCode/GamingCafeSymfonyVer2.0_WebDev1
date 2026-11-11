<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\StationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    #[Route('/booking', name: 'app_booking')]
    public function index(StationRepository $stationRepository): Response
    {
        $pcs = $stationRepository->findBy(['type' => 'PC']);
        $consoles = $stationRepository->findBy(['type' => 'Console']);

        return $this->render('booking/index.html.twig', [
            'pcs' => $pcs,
            'consoles' => $consoles,
        ]);
    }

    #[Route('/booking/reserve/{id}', name: 'app_book_station')]
    public function reserve(int $id, StationRepository $stationRepository, EntityManagerInterface $entityManager): Response
    {
        // 1. Find the selected station
        $station = $stationRepository->find($id);

        if (!$station) {
            throw $this->createNotFoundException('Station not found');
        }

        // 2. Get the currently logged-in user (requires security login)
        $user = $this->getUser();

        if (!$user) {
            // Redirect to login if not logged in
            return $this->redirectToRoute('app_login');
        }

        // 3. Create and save the new booking record
        $booking = new Booking();
        $booking->setStation($station);
        $booking->setUser($user);
        $booking->setStartTime(new \DateTime());
        $booking->setEndTime((new \DateTime())->modify('+2 hours')); // Default 2-hour slot

        $entityManager->persist($booking);
        $entityManager->flush();

        // 4. Add a flash message and redirect back or to a success page
        $this->addFlash('success', 'Station booked successfully for 2 hours!');

        return $this->redirectToRoute('app_booking');
    }
}