<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testAuthenticatedRegularUserVisitingLoginIsRedirectedHome(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get('doctrine')->getRepository(User::class)
            ->findOneBy(['username' => 'mc']);

        $client->loginUser($user);
        $client->request('GET', '/login');

        $this->assertResponseRedirects('/');
    }

    public function testAuthenticatedStaffUserVisitingLoginIsRedirectedToStaffDashboard(): void
    {
        $client = static::createClient();
        $staff = static::getContainer()->get('doctrine')->getRepository(User::class)
            ->findOneBy(['username' => 'staff1']);

        $client->loginUser($staff);
        $client->request('GET', '/login');

        $this->assertResponseRedirects('/staff');
    }

    public function testAuthenticatedAdminVisitingLoginIsRedirectedToAdminDashboard(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get('doctrine')->getRepository(User::class)
            ->findOneBy(['username' => 'yan']);

        $client->loginUser($admin);
        $client->request('GET', '/login');

        $this->assertResponseRedirects('/admin');
    }
}
