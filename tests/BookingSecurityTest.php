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
        $this->assertSelectorTextContains('h1', 'Book your gaming station');
    }
}
