<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\RecordType;
use App\Repository\BookingRepository;
use App\Repository\StationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff')]
#[IsGranted('ROLE_STAFF')]
class StaffController extends AbstractController
{
    #[Route('', name: 'staff_dashboard', methods: ['GET'])]
    public function index(
        BookingRepository $bookingRepository,
        StationRepository $stationRepository
    ): Response {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        $allRecords = $bookingRepository->findBy([], ['id' => 'DESC']);
        $myRecordsCount = $bookingRepository->countByUser($currentUser);
        $totalRecordsCount = $bookingRepository->countTotalBookings();
        $totalStations = $stationRepository->countTotalStations();

        return $this->render('staff/index.html.twig', [
            'records' => $allRecords,
            'myRecordsCount' => $myRecordsCount,
            'totalRecordsCount' => $totalRecordsCount,
            'totalStations' => $totalStations,
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/record/new', name: 'staff_record_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $booking = new Booking();
        $booking->setBookingDate(new \DateTime());
        $booking->setStartTime(new \DateTime());
        $booking->setHours(1);
        $booking->setStatus('CONFIRMED');

        $form = $this->createForm(RecordType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $currentUser */
            $currentUser = $this->getUser();
            $booking->setUser($currentUser);

            $hours = $booking->getHours() ?: 1;
            $startTime = $booking->getStartTime() ?? new \DateTime();
            $booking->setEndTime((clone $startTime)->modify('+' . $hours . ' hours'));

            $entityManager->persist($booking);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Record #%d created successfully under your staff account.', $booking->getId()));
            return $this->redirectToRoute('staff_dashboard');
        }

        return $this->render('staff/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/record/{id}/edit', name: 'staff_record_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Booking $booking,
        EntityManagerInterface $entityManager
    ): Response {
        // Enforce ownership check via Symfony Voter
        // Staff can ONLY edit records they created themselves
        $this->denyAccessUnlessGranted(
            'RECORD_EDIT',
            $booking,
            'Access Denied: You can only edit records you created. Editing admin or other staff records is prohibited.'
        );

        $form = $this->createForm(RecordType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hours = $booking->getHours() ?: 1;
            $startTime = $booking->getStartTime() ?? new \DateTime();
            $booking->setEndTime((clone $startTime)->modify('+' . $hours . ' hours'));

            $entityManager->flush();

            $this->addFlash('success', sprintf('Record #%d updated successfully.', $booking->getId()));
            return $this->redirectToRoute('staff_dashboard');
        }

        return $this->render('staff/edit.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/record/{id}/delete', name: 'staff_record_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Booking $booking,
        EntityManagerInterface $entityManager
    ): Response {
        // Enforce ownership check via Symfony Voter
        // Staff can ONLY delete records they created themselves
        $this->denyAccessUnlessGranted(
            'RECORD_DELETE',
            $booking,
            'Access Denied: You can only delete records you created. Deleting admin or other staff records is prohibited.'
        );

        if ($this->isCsrfTokenValid('delete_staff_record_'.$booking->getId(), $request->getPayload()->getString('_token'))) {
            $id = $booking->getId();
            $entityManager->remove($booking);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Record #%d deleted successfully.', $id));
        } else {
            $this->addFlash('error', 'Invalid security token for record deletion.');
        }

        return $this->redirectToRoute('staff_dashboard');
    }
}
