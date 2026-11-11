<?php

namespace App\Tests;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\StationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingFlowTest extends WebTestCase
{
    public function testBookingCheckoutCreatesReceiptAndAppearsOnProfile(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $stationRepository = static::getContainer()->get(StationRepository::class);
        $user = $userRepository->findOneBy(['username' => 'mc']);
        $station = $stationRepository->findOneBy(['type' => 'Pc']);

        self::assertInstanceOf(User::class, $user);
        self::assertNotNull($station);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/book');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#reviewBookingBtn[type="button"]');
        self::assertSelectorExists(sprintf('.device-card[data-station-id="%d"]', $station->getId()));
        self::assertSelectorExists('#bookingDate[type="date"]');
        self::assertSelectorExists('#bookingTime[type="time"]');
        self::assertSelectorExists('#hoursInput[type="number"]');
        self::assertSelectorExists('#stationInput');

        $bookingDate = new \DateTime(sprintf('2099-12-%02d', random_int(1, 28)));
        $bookingId = null;
        $bookingTime = sprintf('%02d:00', random_int(0, 23));

        try {
            $form = $crawler->filter('#bookingForm')->form([
                'booking[bookingDate]' => $bookingDate->format('Y-m-d'),
                'booking[startTime]' => $bookingTime,
                'booking[hours]' => '3',
                'booking[station]' => (string) $station->getId(),
                'booking[paymentMethod]' => 'GCash',
            ]);

            $client->submit($form);
            self::assertResponseRedirects();

            $location = $client->getResponse()->headers->get('Location');
            self::assertIsString($location);
            self::assertMatchesRegularExpression('~/bookings/(\d+)/receipt$~', $location);
            preg_match('~/bookings/(\d+)/receipt$~', $location, $matches);
            $bookingId = (int) $matches[1];

            $client->followRedirect();
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('.receipt-details', 'STANDARD');
            self::assertSelectorTextContains('.receipt-details', 'GCash');
            self::assertSelectorTextContains('.receipt-details', '₱100');
            self::assertSelectorTextContains('.receipt-status', 'no real payment was taken');

            $client->request('GET', '/profile');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('.profile-booking-list', $station->getName());
            self::assertSelectorTextContains('.profile-booking-list', 'STANDARD');
            self::assertSelectorExists(sprintf('a[href="/bookings/%d/receipt"]', $bookingId));
        } finally {
            if ($bookingId !== null) {
                $entityManager = static::getContainer()->get(EntityManagerInterface::class);
                $entityManager->clear();
                $booking = $entityManager->find(Booking::class, $bookingId);
            } else {
                $entityManager = static::getContainer()->get(EntityManagerInterface::class);
                $entityManager->clear();
                $booking = $entityManager->getRepository(Booking::class)->findOneBy([
                    'user' => $user,
                    'station' => $station,
                    'bookingDate' => $bookingDate,
                    'hours' => 3,
                    'paymentMethod' => 'GCash',
                ]);
            }

            if ($booking instanceof Booking) {
                $entityManager->remove($booking);
                $entityManager->flush();
            }
        }
    }
}
