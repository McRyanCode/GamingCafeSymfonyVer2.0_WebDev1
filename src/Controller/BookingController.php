<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Station;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BookingController extends AbstractController
{
    #[Route('/book', name: 'app_booking')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $booking->setUser($user);

            if (!$booking->getBookingDate()) {
                $booking->setBookingDate(new \DateTime());
            }

            if (!$booking->getStartTime()) {
                $booking->setStartTime(new \DateTime());
            }

            $hours = $booking->getHours() ?: 1;
            $endTime = (clone $booking->getStartTime())->modify('+' . $hours . ' hours');
            $booking->setEndTime($endTime);
            $booking->setStatus('CONFIRMED');

            // Find matching station if selected by user
            $stationName = $form->has('station') ? $form->get('station')->getData() : null;
            if ($stationName) {
                $station = $entityManager->getRepository(Station::class)->findOneBy(['name' => $stationName]);
                if ($station) {
                    $booking->setStation($station);
                }
            }

            $entityManager->persist($booking);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Station booked successfully for %d hour(s)!', $hours));

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_bookings');
            } elseif ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('staff_dashboard');
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('booking/index.html.twig', [
            'bookingForm' => $form->createView(),
        ]);
    }
}
