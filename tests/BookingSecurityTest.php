<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingSecurityTest extends WebTestCase
{
    public function testAnonymousAccessToBookingRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/book');

        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedUserCanAccessBookingPage(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => 'mc']);

        $client->loginUser($user);
        $client->request('GET', '/book');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Book Your Gaming Station');
        $this->assertSelectorExists('.booking-page-header a[href="/"]');
        $this->assertSelectorExists('.visually-hidden select#stationInput');
    }

    public function testAdminBookingLinkReturnsToAdminArea(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => 'yan']);
        self::assertInstanceOf(User::class, $user);

        $client->loginUser($user);
        $client->request('GET', '/book');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.booking-page-header a[href="/admin"]');
    }

    public function testStaffBookingLinkReturnsToStaffArea(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => 'staff1']);
        self::assertInstanceOf(User::class, $user);

        $client->loginUser($user);
        $client->request('GET', '/book');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.booking-page-header a[href="/staff"]');
    }

    public function testProfileHomeAndLogoutLinksAreStyledAsButtons(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => 'mc']);
        self::assertInstanceOf(User::class, $user);

        $client->loginUser($user);
        $client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.profile-actions a.profile-action-button[href="/"]');
        self::assertSelectorExists('.profile-actions a.profile-action-button[href="/logout"]');
    }
}
