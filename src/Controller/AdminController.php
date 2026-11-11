<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\UserRepository;
use App\Repository\BookingRepository;
use App\Repository\StationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    #[Route('/dashboard', name: 'admin_dashboard_view', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        BookingRepository $bookingRepository,
        StationRepository $stationRepository
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $userRepository->countTotalUsers(),
            'totalStaff' => $userRepository->countStaffUsers(),
            'totalAdmins' => $userRepository->countAdminUsers(),
            'totalCustomers' => $userRepository->countCustomerUsers(),
            'totalBookings' => $bookingRepository->countTotalBookings(),
            'activeBookings' => $bookingRepository->countActiveBookings(),
            'totalStations' => $stationRepository->countTotalStations(),
            'recentBookings' => $bookingRepository->findRecentBookings(6),
        ]);
    }

    #[Route('/customers', name: 'admin_customers', methods: ['GET'])]
    public function customers(UserRepository $userRepository): Response
    {
        return $this->render('admin/customers.html.twig', [
            'users' => $userRepository->findAllOrderedByLatest(),
        ]);
    }

    #[Route('/bookings', name: 'admin_bookings', methods: ['GET'])]
    public function bookings(BookingRepository $bookingRepository): Response
    {
        return $this->render('admin/bookings.html.twig', [
            'bookings' => $bookingRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/bookings/{id}/status', name: 'admin_booking_status', methods: ['POST'])]
    public function updateBookingStatus(
        Request $request,
        Booking $booking,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('status_booking_'.$booking->getId(), $request->getPayload()->getString('_token'))) {
            $newStatus = $request->getPayload()->getString('status', 'CONFIRMED');
            $booking->setStatus(strtoupper($newStatus));
            $entityManager->flush();
            $this->addFlash('success', sprintf('Booking #%d status updated to %s.', $booking->getId(), $booking->getStatus()));
        } else {
            $this->addFlash('error', 'Invalid security token for updating booking status.');
        }

        return $this->redirectToRoute('admin_bookings');
    }

    #[Route('/bookings/{id}/delete', name: 'admin_booking_delete', methods: ['POST'])]
    public function deleteBooking(
        Request $request,
        Booking $booking,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete_booking_'.$booking->getId(), $request->getPayload()->getString('_token'))) {
            $id = $booking->getId();
            $entityManager->remove($booking);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Booking record #%d has been deleted successfully.', $id));
        } else {
            $this->addFlash('error', 'Invalid security token for booking deletion.');
        }

        return $this->redirectToRoute('admin_bookings');
    }
}
