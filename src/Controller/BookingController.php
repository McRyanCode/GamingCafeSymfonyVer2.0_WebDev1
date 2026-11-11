<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Station;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\StationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BookingController extends AbstractController
{
    #[Route('/book', name: 'app_booking', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        StationRepository $stationRepository
    ): Response {
        $booking = new Booking();
        $booking->setBookingDate(new \DateTime('today'));

        $startTime = new \DateTime();
        $startTime->modify('+15 minutes');
        $minute = (int) ceil((int) $startTime->format('i') / 15) * 15;
        $startTime->setTime((int) $startTime->format('H'), $minute);
        $booking->setStartTime($startTime);
        $booking->setHours(1);

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $station = $booking->getStation();
            $hours = $booking->getHours();

            if ($station !== null && $hours !== null && $hours >= 1 && $hours <= 12) {
                $pricing = $this->calculatePricing($station, $hours);
                $booking->setTier($pricing['tier']);
                $booking->setTotalPrice($pricing['total']);
            }

            $bookingDate = $booking->getBookingDate();
            $selectedTime = $booking->getStartTime();

            if ($bookingDate !== null && $selectedTime !== null) {
                $startTime = clone $bookingDate;
                $startTime->setTime(
                    (int) $selectedTime->format('H'),
                    (int) $selectedTime->format('i')
                );

                if ($startTime < new \DateTime()) {
                    $form->addError(new FormError('Choose a start time that is in the future.'));
                } else {
                    $booking->setStartTime($startTime);
                    $booking->setEndTime((clone $startTime)->modify(sprintf('+%d hours', $hours ?? 1)));
                }
            }

            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $booking->setUser($user);
            $booking->setStatus('CONFIRMED');
            $booking->setPaymentStatus(
                $booking->getPaymentMethod() === 'Cash at Counter' ? 'PENDING' : 'DEMO_PAID'
            );

            if ($form->isValid()) {
                $entityManager->persist($booking);
                $entityManager->flush();

                return $this->redirectToRoute('app_booking_receipt', ['id' => $booking->getId()]);
            }
        }

        return $this->render('booking/index.html.twig', [
            'bookingForm' => $form->createView(),
            'stations' => $stationRepository->findBy([], ['type' => 'ASC', 'name' => 'ASC']),
            'today' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
        ]);
    }

    #[Route('/bookings/{id}/receipt', name: 'app_booking_receipt', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function receipt(Booking $booking): Response
    {
        if ($booking->getUser()?->getId() !== $this->getUser()?->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('booking/receipt.html.twig', [
            'booking' => $booking,
        ]);
    }

    /**
     * @return array{tier: string, total: int}
     */
    private function calculatePricing(Station $station, int $hours): array
    {
        $isConsole = str_contains(strtolower((string) $station->getType()), 'console');
        $hourlyRate = $isConsole ? 30 : 40;

        if ($hours <= 2) {
            return ['tier' => 'BASIC', 'total' => $hours * $hourlyRate];
        }

        if ($hours <= 4) {
            $packagePrice = $isConsole ? 80 : 100;

            return ['tier' => 'STANDARD', 'total' => $packagePrice + (($hours - 3) * $hourlyRate)];
        }

        $packagePrice = $isConsole ? 120 : 150;

        return ['tier' => 'PREMIUM', 'total' => $packagePrice + (($hours - 5) * $hourlyRate)];
    }
}
