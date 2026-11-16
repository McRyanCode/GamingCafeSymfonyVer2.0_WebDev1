<?php

namespace App\Tests;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\StationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountAndBookingManagementTest extends WebTestCase
{
    public function testAuthenticatedUserMustProvideCurrentPasswordToChangeIt(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => 'mc']);

        self::assertInstanceOf(User::class, $user);
        $userId = $user->getId();
        $originalHash = $user->getPassword();
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, 'current-password'));
        $temporaryHash = $user->getPassword();
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser($user);

        try {
            $crawler = $client->request('GET', '/profile');
            self::assertResponseIsSuccessful();

            $form = $crawler->selectButton('Change password')->form([
                'change_password[currentPassword]' => 'incorrect-current-password',
                'change_password[newPassword][first]' => 'new-secure-password',
                'change_password[newPassword][second]' => 'new-secure-password',
            ]);
            $client->submit($form);

            self::assertResponseIsSuccessful();
            self::assertStringContainsString('The current password is incorrect.', (string) $client->getResponse()->getContent());

            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->clear();
            $user = $entityManager->find(User::class, $userId);
            self::assertInstanceOf(User::class, $user);
            self::assertSame($temporaryHash, $user->getPassword());

            $crawler = $client->request('GET', '/profile');
            $form = $crawler->selectButton('Change password')->form([
                'change_password[currentPassword]' => 'current-password',
                'change_password[newPassword][first]' => 'new-secure-password',
                'change_password[newPassword][second]' => 'new-secure-password',
            ]);
            $client->submit($form);

            self::assertResponseRedirects('/login');
            $entityManager->clear();
            $user = $entityManager->find(User::class, $userId);
            self::assertInstanceOf(User::class, $user);
            self::assertNotSame($temporaryHash, $user->getPassword());
            self::assertTrue(
                static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'new-secure-password')
            );
        } finally {
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->clear();
            $user = $entityManager->find(User::class, $userId);
            if ($user instanceof User && $originalHash !== null) {
                $user->setPassword($originalHash);
                $entityManager->flush();
            }
        }
    }

    public function testForgotPasswordPageDirectsUsersToAnAdministrator(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forgot-password');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.auth-form', 'contact the cafe administrator');

        $client->request('POST', '/forgot-password', [
            'identifier' => 'mc',
            'email' => 'mc@example.com',
            'new_password' => 'attacker-chosen-password',
        ]);

        self::assertResponseStatusCodeSame(405);
    }

    public function testAdminCanDisableAndReenableStaffAccounts(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $staff = $userRepository->findOneBy(['username' => 'staff1']);
        $admin = $userRepository->findOneBy(['username' => 'yan']);

        self::assertInstanceOf(User::class, $staff);
        self::assertInstanceOf(User::class, $admin);
        $staffId = $staff->getId();
        $wasActive = $staff->isActive();

        $client->loginUser($admin);

        try {
            $crawler = $client->request('GET', '/admin/customers');
            $form = $crawler->filter(sprintf('form[action="/admin/user/%d/status"]', $staffId))->form();
            $client->submit($form);
            self::assertResponseRedirects('/admin/customers');

            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->clear();
            $staff = $entityManager->find(User::class, $staffId);
            self::assertInstanceOf(User::class, $staff);
            self::assertFalse($staff->isActive());

            $crawler = $client->request('GET', '/admin/customers');
            $form = $crawler->filter(sprintf('form[action="/admin/user/%d/status"]', $staffId))->form();
            $client->submit($form);
            self::assertResponseRedirects('/admin/customers');

            $entityManager->clear();
            $staff = $entityManager->find(User::class, $staffId);
            self::assertInstanceOf(User::class, $staff);
            self::assertTrue($staff->isActive());
        } finally {
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->clear();
            $staff = $entityManager->find(User::class, $staffId);
            if ($staff instanceof User) {
                $staff->setActive($wasActive);
                $entityManager->flush();
            }
        }
    }

    public function testDisabledAccountCannotKeepAnExistingSessionOrLogIn(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $staff = $userRepository->findOneBy(['username' => 'staff1']);

        self::assertInstanceOf(User::class, $staff);
        $staffId = $staff->getId();
        $originalHash = $staff->getPassword();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $staff->setPassword($passwordHasher->hashPassword($staff, 'temporary-staff-password'));
        $wasActive = $staff->isActive();
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser($staff);

        try {
            $client->request('GET', '/staff');
            self::assertResponseIsSuccessful();

            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->clear();
            $staff = $entityManager->find(User::class, $staffId);
            self::assertInstanceOf(User::class, $staff);
            $staff->setActive(false);
            $entityManager->flush();

            $client->request('GET', '/staff');
            self::assertResponseRedirects('/login');

            $crawler = $client->request('GET', '/login');
            $form = $crawler->filter('form')->form([
                'username' => 'staff1',
                'password' => 'temporary-staff-password',
            ]);
            $client->submit($form);

            $client->followRedirect();
            self::assertResponseIsSuccessful();
            self::assertStringContainsString('This account has been disabled.', (string) $client->getResponse()->getContent());
        } finally {
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $entityManager->clear();
            $staff = $entityManager->find(User::class, $staffId);
            if ($staff instanceof User) {
                $staff->setActive($wasActive);
                if ($originalHash !== null) {
                    $staff->setPassword($originalHash);
                }
                $entityManager->flush();
            }
        }
    }

    public function testAdminCanEditAllBookingDetails(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $stationRepository = static::getContainer()->get(StationRepository::class);
        $customer = $userRepository->findOneBy(['username' => 'mc']);
        $admin = $userRepository->findOneBy(['username' => 'yan']);
        $station = $stationRepository->findOneBy([]);

        self::assertInstanceOf(User::class, $customer);
        self::assertInstanceOf(User::class, $admin);
        self::assertNotNull($station);

        $booking = new Booking();
        $booking->setUser($customer);
        $booking->setStation($station);
        $booking->setBookingDate(new \DateTime('2099-12-01'));
        $booking->setStartTime(new \DateTime('2099-12-01 09:00:00'));
        $booking->setEndTime(new \DateTime('2099-12-01 11:00:00'));
        $booking->setHours(2);
        $booking->setTier('BASIC');
        $booking->setStatus('CONFIRMED');
        $booking->setPaymentMethod('Cash at Counter');
        $booking->setPaymentStatus('PENDING');
        $booking->setTotalPrice(80);
        $entityManager->persist($booking);
        $entityManager->flush();
        $bookingId = $booking->getId();

        $client->loginUser($admin);

        try {
            $crawler = $client->request('GET', '/admin/bookings/'.$bookingId.'/edit');
            self::assertResponseIsSuccessful();

            $form = $crawler->selectButton('Save Changes')->form([
                'admin_booking[user]' => (string) $admin->getId(),
                'admin_booking[station]' => (string) $station->getId(),
                'admin_booking[tier]' => 'PREMIUM',
                'admin_booking[bookingDate]' => '2099-12-03',
                'admin_booking[startTime]' => '11:30',
                'admin_booking[hours]' => '4',
                'admin_booking[status]' => 'COMPLETED',
                'admin_booking[paymentMethod]' => 'GCash',
                'admin_booking[paymentStatus]' => 'DEMO_PAID',
                'admin_booking[totalPrice]' => '150',
            ]);
            $client->submit($form);

            self::assertResponseRedirects('/admin/bookings');
            $entityManager->clear();
            $updatedBooking = $entityManager->find(Booking::class, $bookingId);
            self::assertInstanceOf(Booking::class, $updatedBooking);
            self::assertSame($admin->getId(), $updatedBooking->getUser()?->getId());
            self::assertSame('PREMIUM', $updatedBooking->getTier());
            self::assertSame('2099-12-03', $updatedBooking->getBookingDate()?->format('Y-m-d'));
            self::assertSame('11:30', $updatedBooking->getStartTime()?->format('H:i'));
            self::assertSame('15:30', $updatedBooking->getEndTime()?->format('H:i'));
            self::assertSame(4, $updatedBooking->getHours());
            self::assertSame('COMPLETED', $updatedBooking->getStatus());
            self::assertSame('GCash', $updatedBooking->getPaymentMethod());
            self::assertSame('DEMO_PAID', $updatedBooking->getPaymentStatus());
            self::assertSame(150, $updatedBooking->getTotalPrice());
        } finally {
            $entityManager->clear();
            $booking = $entityManager->find(Booking::class, $bookingId);
            if ($booking instanceof Booking) {
                $entityManager->remove($booking);
                $entityManager->flush();
            }
        }
    }
}
